<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Services\PartnerCommissionReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GeneratePartnerCommissionReport extends Command
{
    protected $signature = 'partners:commission-report
        {partner_id? : ID del partner (omesso con --all elabora tutti i partner attivi)}
        {--month= : Mese (1-12). Default: mese precedente}
        {--year= : Anno (YYYY). Default: anno del mese precedente}
        {--all : Elabora tutti i partner attivi}';

    protected $description = 'Genera il riepilogo delle commissioni maturate da un partner (o da tutti) in un dato mese/anno.';

    public function __construct(private readonly PartnerCommissionReportService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        [$year, $month] = $this->resolvePeriod();

        $partners = $this->resolvePartners();
        if ($partners->isEmpty()) {
            $this->error('Nessun partner selezionato.');

            return self::FAILURE;
        }

        $this->info(sprintf('Report commissioni · %02d/%d', $month, $year));
        $this->newLine();

        foreach ($partners as $partner) {
            $report = $this->service->buildReport($partner, $year, $month);
            $this->renderPartnerReport($partner, $report);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolvePeriod(): array
    {
        $previous = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $month = (int) ($this->option('month') ?: $previous->month);
        $year = (int) ($this->option('year') ?: $previous->year);

        if ($month < 1 || $month > 12) {
            $this->error("Mese non valido: {$month}");
            exit((int) self::FAILURE);
        }

        return [$year, $month];
    }

    /**
     * @return \Illuminate\Support\Collection<int,Partner>
     */
    private function resolvePartners(): \Illuminate\Support\Collection
    {
        if ($this->option('all')) {
            return Partner::where('is_active', 1)->orderBy('partner_name')->get();
        }

        $partnerId = $this->argument('partner_id');
        if (! $partnerId) {
            $this->error('Passare un partner_id o usare --all.');
            exit((int) self::FAILURE);
        }

        $partner = Partner::find((int) $partnerId);
        if (! $partner) {
            $this->error("Partner #{$partnerId} non trovato.");
            exit((int) self::FAILURE);
        }

        return collect([$partner]);
    }

    /**
     * @param  array<string,mixed>  $report
     */
    private function renderPartnerReport(Partner $partner, array $report): void
    {
        $totals = $report['totals'];
        $this->line(sprintf(
            '<fg=cyan>#%d — %s</> (%s)',
            $partner->id,
            $partner->partner_name,
            $partner->partner_code ?: '—',
        ));

        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Ordini pagati', number_format($report['orders_count'], 0, ',', '.')],
                ['Biglietti', number_format($report['items_count'], 0, ',', '.')],
                ['Lordo biglietti', self::euro($totals['gross'])],
                ['Commissioni Miticko (fissa)', self::euro($totals['miticko_fixed'])],
                ['Commissioni Miticko (variabile)', self::euro($totals['miticko_variable'])],
                ['Commissioni Miticko (totale)', self::euro($totals['miticko_total'])],
                ['Commissioni bancarie', self::euro($totals['bank'])],
                ['Prevendita (Miticko)', self::euro($totals['presale'])],
                ['Netto partner', self::euro($totals['partner_net'])],
            ],
        );
    }

    private static function euro(float $v): string
    {
        return number_format($v, 2, ',', '.').' €';
    }
}
