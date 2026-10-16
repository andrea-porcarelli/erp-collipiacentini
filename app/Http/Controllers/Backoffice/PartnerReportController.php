<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerReport;
use App\Models\PartnerReportRecipient;
use App\Models\User;
use App\Services\PartnerCommissionReportService;
use App\Services\PartnerReportArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PartnerReportController extends Controller
{
    private const MITICKO_ROLE_LABELS = [
        'god' => 'Admin miticko',
        'admin' => 'Collaboratore miticko',
    ];

    private const PARTNER_ROLE_LABELS = [
        'admin' => 'Partner admin',
        'partner' => 'Partner collaboratore',
    ];

    private const MONTHS_IT = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    public function __construct(
        private readonly PartnerCommissionReportService $service,
        private readonly PartnerReportArchiveService $archive,
    ) {}

    public function show(Request $request, Partner $partner): View
    {
        [$year, $month] = $this->resolvePeriod($request);

        $partner->loadMissing('billing', 'logo');
        $report = $this->service->buildReport($partner, $year, $month);
        $availablePeriods = $this->service->availablePeriods($partner);

        return view('backoffice.partners.report', [
            'path' => 'partners',
            'partner' => $partner,
            'report' => $report,
            'availablePeriods' => $availablePeriods,
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }

    public function pdf(Request $request, Partner $partner): Response
    {
        [$year, $month] = $this->resolvePeriod($request);

        $filename = sprintf(
            'report-commissioni-%s-%04d-%02d.pdf',
            \Illuminate\Support\Str::slug($partner->partner_code ?: $partner->partner_name ?: 'partner'),
            $year,
            $month,
        );

        return $this->archive->renderPdf($partner, $year, $month)->download($filename);
    }

    public function updateSettings(Request $request, Partner $partner): JsonResponse
    {
        $this->authorizeGod();

        $data = $request->validate([
            'report_auto_send_enabled' => ['required', 'boolean'],
        ]);

        $partner->update($data);

        return response()->json([
            'message' => 'Impostazioni salvate',
            'report_auto_send_enabled' => (bool) $partner->report_auto_send_enabled,
        ]);
    }

    public function updateSubscriptions(Request $request, Partner $partner): JsonResponse
    {
        $this->authorizePartnerAccess($partner);

        $data = $request->validate([
            'subscriptions' => ['required', 'array'],
            'subscriptions.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'subscriptions.*.auto_send' => ['required', 'boolean'],
        ]);

        $authUser = Auth::user();
        $isGod = $authUser->role === 'god';
        $existingUserIds = $partner->reportSubscribers()->pluck('users.id')->all();

        foreach ($data['subscriptions'] as $row) {
            $user = User::find($row['user_id']);
            if (! $user) {
                continue;
            }

            if ($isGod) {
                $allowed = ($user->partner_id === $partner->id)
                    || (in_array($user->role, ['god', 'admin'], true) && $user->partner_id === null);
            } else {
                $allowed = $user->partner_id === $partner->id;
            }

            if (! $allowed) {
                continue;
            }

            $pivotAttrs = ['auto_send' => (bool) $row['auto_send']];
            if (in_array($user->id, $existingUserIds, true)) {
                $partner->reportSubscribers()->updateExistingPivot($user->id, $pivotAttrs);
            } else {
                $partner->reportSubscribers()->attach($user->id, $pivotAttrs);
            }
        }

        return response()->json(['message' => 'Preferenze destinatari salvate']);
    }

    public function downloadArchived(Partner $partner, PartnerReport $partnerReport): BinaryFileResponse|Response
    {
        $this->authorizePartnerAccess($partner);

        abort_unless($partnerReport->partner_id === $partner->id, 404);

        $disk = Storage::disk('local');
        abort_unless($partnerReport->pdf_path && $disk->exists($partnerReport->pdf_path), 404);

        $filename = sprintf(
            'report-%s-%04d-%02d.pdf',
            \Illuminate\Support\Str::slug($partner->partner_code ?: $partner->partner_name ?: 'partner'),
            $partnerReport->year,
            $partnerReport->month,
        );

        return response()->download($disk->path($partnerReport->pdf_path), $filename);
    }

    public function logsData(Request $request, Partner $partner): JsonResponse
    {
        $this->authorizePartnerAccess($partner);

        $query = PartnerReportRecipient::query()
            ->join('partner_reports', 'partner_reports.id', '=', 'partner_report_recipients.partner_report_id')
            ->where('partner_reports.partner_id', $partner->id)
            ->select(
                'partner_report_recipients.id',
                'partner_report_recipients.email',
                'partner_report_recipients.status',
                'partner_report_recipients.sent_at',
                'partner_report_recipients.partner_report_id',
                'partner_reports.year as pr_year',
                'partner_reports.month as pr_month',
                'partner_reports.mode as pr_mode',
                'partner_reports.pdf_path as pr_pdf_path',
                'partner_reports.created_at as pr_created_at',
            )
            ->orderByDesc('partner_report_recipients.created_at');

        return datatables()->of($query)
            ->editColumn('pr_year', fn ($r) => $r->pr_year.' - '.(self::MONTHS_IT[$r->pr_month] ?? $r->pr_month))
            ->editColumn('sent_at', function ($r) {
                if (! $r->sent_at) {
                    return '<span class="text-secondary">—</span>';
                }
                $dt = Carbon::parse($r->sent_at);

                return $dt->format('d M Y').'<br><small class="text-secondary">'.$dt->format('H:i').'</small>';
            })
            ->editColumn('pr_mode', fn ($r) => $r->pr_mode === 'auto' ? 'Automatica' : 'Manuale')
            ->editColumn('status', function ($r) {
                return match ($r->status) {
                    'sent' => '<span class="badge bg-success-subtle text-success text-uppercase">Completato</span>',
                    'failed' => '<span class="badge bg-danger-subtle text-danger text-uppercase">Errore</span>',
                    default => '<span class="badge bg-secondary-subtle text-secondary text-uppercase">In coda</span>',
                };
            })
            ->addColumn('download', function ($r) use ($partner) {
                if (! $r->pr_pdf_path) {
                    return '<span class="text-secondary">—</span>';
                }
                $url = route('partners.report.download-archived', [
                    'partner' => $partner->id,
                    'partnerReport' => $r->partner_report_id,
                ]);

                return '<a href="'.$url.'" class="link-primary"><i class="fa-regular fa-download me-1"></i>Scarica file</a>';
            })
            ->rawColumns(['sent_at', 'status', 'download'])
            ->toJson();
    }

    public function subscribersMitickoData(Request $request, Partner $partner): JsonResponse
    {
        $this->authorizeGod();

        $query = User::query()
            ->whereIn('users.role', ['god', 'admin'])
            ->whereNull('users.partner_id')
            ->leftJoin('partner_report_subscriptions', function ($join) use ($partner) {
                $join->on('partner_report_subscriptions.user_id', '=', 'users.id')
                    ->where('partner_report_subscriptions.partner_id', '=', $partner->id);
            })
            ->select('users.id', 'users.email', 'users.role', 'partner_report_subscriptions.auto_send')
            ->orderBy('users.email');

        return $this->buildSubscribersResponse($query, self::MITICKO_ROLE_LABELS);
    }

    public function subscribersPartnerData(Request $request, Partner $partner): JsonResponse
    {
        $this->authorizePartnerAccess($partner);

        $query = User::query()
            ->where('users.partner_id', $partner->id)
            ->leftJoin('partner_report_subscriptions', function ($join) use ($partner) {
                $join->on('partner_report_subscriptions.user_id', '=', 'users.id')
                    ->where('partner_report_subscriptions.partner_id', '=', $partner->id);
            })
            ->select('users.id', 'users.email', 'users.role', 'partner_report_subscriptions.auto_send')
            ->orderBy('users.email');

        return $this->buildSubscribersResponse($query, self::PARTNER_ROLE_LABELS);
    }

    private function buildSubscribersResponse($query, array $roleLabels): JsonResponse
    {
        return datatables()->of($query)
            ->editColumn('role', fn ($u) => $roleLabels[$u->role] ?? $u->role)
            ->addColumn('toggle', function ($u) {
                $checked = $u->auto_send ? 'checked' : '';

                return '<div class="switch-cell">'
                    .'<input type="checkbox" class="js-switch switch-report-subscription" '
                    .'data-user-id="'.$u->id.'" name="subscription['.$u->id.']" '.$checked.' />'
                    .'</div>';
            })
            ->rawColumns(['toggle'])
            ->toJson();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolvePeriod(Request $request): array
    {
        $now = Carbon::now();
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = $now->year;
        }

        return [$year, $month];
    }

    private function authorizeGod(): void
    {
        abort_unless(Auth::user()?->role === 'god', 403);
    }

    private function authorizePartnerAccess(Partner $partner): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }
        if ($user->role === 'god') {
            return;
        }
        abort_unless($user->partner_id === $partner->id, 403);
    }
}
