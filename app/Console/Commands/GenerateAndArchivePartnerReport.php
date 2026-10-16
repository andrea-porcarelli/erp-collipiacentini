<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerReport;
use App\Services\PartnerReportArchiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GenerateAndArchivePartnerReport extends Command
{
    protected $signature = 'partners:report-archive
        {partner_id : ID del partner}
        {--month= : Mese (1-12). Default: mese precedente}
        {--year= : Anno (YYYY). Default: anno del mese precedente}';

    protected $description = 'Genera il PDF del report commissioni di un partner, lo archivia su storage e crea una riga partner_reports.';

    public function __construct(private readonly PartnerReportArchiveService $archive)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $partner = Partner::find((int) $this->argument('partner_id'));
        if (! $partner) {
            $this->error('Partner non trovato.');

            return self::FAILURE;
        }

        $previous = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $month = (int) ($this->option('month') ?: $previous->month);
        $year = (int) ($this->option('year') ?: $previous->year);

        if ($month < 1 || $month > 12) {
            $this->error("Mese non valido: {$month}");

            return self::FAILURE;
        }

        $this->info(sprintf('Archivio report %s · %02d/%d', $partner->partner_name, $month, $year));

        $report = $this->archive->generateAndStore($partner, $year, $month, PartnerReport::MODE_MANUAL);

        $this->info(sprintf('OK · partner_reports id=%d', $report->id));
        $this->line('  path: '.Storage::disk('local')->path($report->pdf_path));
        $this->line('  size: '.number_format(Storage::disk('local')->size($report->pdf_path)).' byte');

        return self::SUCCESS;
    }
}
