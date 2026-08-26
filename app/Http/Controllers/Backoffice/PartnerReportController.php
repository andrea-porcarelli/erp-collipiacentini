<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\PartnerCommissionReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PartnerReportController extends Controller
{
    public function __construct(private readonly PartnerCommissionReportService $service) {}

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

        $partner->loadMissing('billing', 'logo');
        $report = $this->service->buildReport($partner, $year, $month);

        $pdf = Pdf::loadView('backoffice.partners._report_pdf', [
            'partner' => $partner,
            'report' => $report,
        ])->setPaper('a4');

        $filename = sprintf(
            'report-commissioni-%s-%04d-%02d.pdf',
            \Illuminate\Support\Str::slug($partner->partner_code ?: $partner->partner_name ?: 'partner'),
            $year,
            $month,
        );

        return $pdf->download($filename);
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
}
