<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PartnerReportArchiveService
{
    public function __construct(private readonly PartnerCommissionReportService $commissionService) {}

    /**
     * Rende il PDF del report commissioni senza persistenza (preview on-the-fly).
     */
    public function renderPdf(Partner $partner, int $year, int $month): DomPdfInstance
    {
        $partner->loadMissing('billing', 'logo');
        $report = $this->commissionService->buildReport($partner, $year, $month);

        return Pdf::loadView('backoffice.partners._report_pdf', [
            'partner' => $partner,
            'report' => $report,
        ])->setPaper('a4');
    }

    /**
     * Genera il PDF, lo salva su disk `local` e crea una riga partner_reports.
     * Permette più generazioni per lo stesso periodo (ogni invocazione = nuovo file + nuova riga).
     */
    public function generateAndStore(
        Partner $partner,
        int $year,
        int $month,
        string $mode,
        ?User $triggeredBy = null,
    ): PartnerReport {
        $pdf = $this->renderPdf($partner, $year, $month);

        $path = sprintf(
            'partner-reports/%d/report-%04d-%02d-%s.pdf',
            $partner->id,
            $year,
            $month,
            Carbon::now()->format('YmdHis'),
        );

        Storage::disk('local')->put($path, $pdf->output());

        return PartnerReport::create([
            'partner_id' => $partner->id,
            'year' => $year,
            'month' => $month,
            'mode' => $mode,
            'pdf_path' => $path,
            'triggered_by_user_id' => $triggeredBy?->id,
        ]);
    }
}
