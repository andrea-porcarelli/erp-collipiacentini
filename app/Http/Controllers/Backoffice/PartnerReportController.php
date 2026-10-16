<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerReport;
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

        $sync = [];
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

            $sync[$user->id] = ['auto_send' => (bool) $row['auto_send']];
        }

        $partner->reportSubscribers()->sync($sync);

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
