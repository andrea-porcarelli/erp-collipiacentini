<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Partner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PartnerCommissionReportService
{
    public function __construct(private readonly InvoiceCalculatorService $calculator) {}

    /**
     * Costruisce il riepilogo commissioni per un partner in un dato mese/anno.
     * Include solo ordini con paid_at ricadente nel periodo e stato paid/completed.
     *
     * @return array{
     *     partner: Partner,
     *     period: array{year:int,month:int,label:string,from:Carbon,to:Carbon},
     *     orders_count: int,
     *     items_count: int,
     *     totals: array<string,float>,
     *     rows: array<int,array<string,mixed>>,
     * }
     */
    public function buildReport(Partner $partner, int $year, int $month): array
    {
        $from = CarbonImmutable::create($year, $month, 1, 0, 0, 0);
        $to = $from->endOfMonth();

        $orders = $this->fetchOrders($partner, $from, $to);

        $rows = [];
        $totals = [
            'gross' => 0.0,
            'miticko_fixed' => 0.0,
            'miticko_variable' => 0.0,
            'miticko_total' => 0.0,
            'bank' => 0.0,
            'presale' => 0.0,
            'partner_net' => 0.0,
        ];
        $itemsCount = 0;

        foreach ($orders as $order) {
            $breakdown = $this->breakdownForOrder($order);
            $rows[] = [
                'order' => $order,
                'items_count' => $breakdown['items_count'],
                'gross' => $breakdown['gross'],
                'miticko_fixed' => $breakdown['miticko_fixed'],
                'miticko_variable' => $breakdown['miticko_variable'],
                'miticko' => $breakdown['miticko_total'],
                'bank' => $breakdown['bank'],
                'presale' => $breakdown['presale'],
                'net' => $breakdown['partner_net'],
            ];

            $itemsCount += $breakdown['items_count'];
            $totals['gross'] += $breakdown['gross'];
            $totals['miticko_fixed'] += $breakdown['miticko_fixed'];
            $totals['miticko_variable'] += $breakdown['miticko_variable'];
            $totals['miticko_total'] += $breakdown['miticko_total'];
            $totals['bank'] += $breakdown['bank'];
            $totals['presale'] += $breakdown['presale'];
            $totals['partner_net'] += $breakdown['partner_net'];
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return [
            'partner' => $partner,
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => $this->periodLabel($year, $month),
                'from' => Carbon::instance($from),
                'to' => Carbon::instance($to),
            ],
            'orders_count' => $orders->count(),
            'items_count' => $itemsCount,
            'totals' => $totals,
            'rows' => $rows,
        ];
    }

    /**
     * Scompone un singolo ordine con le stesse regole di InvoiceCalculatorService,
     * più il totale lordo (ticket_base) e il netto atteso per il partner.
     *
     * @return array<string,float|int>
     */
    private function breakdownForOrder(Order $order): array
    {
        $order->loadMissing(['orderProducts.items.variant', 'partner']);

        $gross = 0.0;
        $itemsCount = 0;
        foreach ($order->orderProducts as $op) {
            foreach ($op->items as $item) {
                $gross += (float) $item->subtotal;
                $itemsCount += (int) $item->quantity;
            }
        }

        $computed = $this->calculator->computeForOrder($order);
        $partnerLines = collect($computed[Invoice::RECIPIENT_PARTNER]['lines'] ?? []);
        $customerLines = collect($computed[Invoice::RECIPIENT_CUSTOMER]['lines'] ?? []);

        $miticko = (float) $partnerLines
            ->where('code', InvoiceCalculatorService::LINE_MITICKO)
            ->sum('total');
        $bank = (float) $partnerLines
            ->where('code', InvoiceCalculatorService::LINE_BANK)
            ->sum('total');
        $presale = (float) $customerLines
            ->where('code', InvoiceCalculatorService::LINE_PRESALE)
            ->sum('total');

        [$mitickoFixed, $mitickoVariable] = $this->splitMitickoCommission($order);

        return [
            'items_count' => $itemsCount,
            'gross' => round($gross, 2),
            'miticko_fixed' => round($mitickoFixed, 2),
            'miticko_variable' => round($mitickoVariable, 2),
            'miticko_total' => round($miticko, 2),
            'bank' => round($bank, 2),
            'presale' => round($presale, 2),
            'partner_net' => round($gross - $miticko - $bank, 2),
        ];
    }

    /**
     * La quota Miticko è "fissa per biglietto + variabile % sul subtotale":
     * espongo entrambe le componenti per il report leggibile.
     *
     * @return array{0:float,1:float}
     */
    private function splitMitickoCommission(Order $order): array
    {
        $partner = $order->partner;
        $fixed = 0.0;
        $variable = 0.0;

        foreach ($order->orderProducts as $op) {
            foreach ($op->items as $item) {
                $qty = max((int) $item->quantity, 1);
                $subtotal = round((float) $item->unit_price * $qty, 2);
                $fixedRate = (float) ($item->partner_commission_miticko_fixed ?? $partner?->commission_miticko_fixed ?? 0);
                $varRate = (float) ($item->partner_commission_miticko_variable ?? $partner?->commission_miticko_variable ?? 0);

                $fixed += $fixedRate * $qty;
                $variable += $subtotal * $varRate / 100;
            }
        }

        return [$fixed, $variable];
    }

    private function fetchOrders(Partner $partner, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Order::query()
            ->where('partner_id', $partner->id)
            ->whereIn('order_status', [OrderStatus::PAID->value, OrderStatus::COMPLETED->value])
            ->whereBetween('paid_at', [$from, $to])
            ->with(['orderProducts.product', 'orderProducts.items.variant'])
            ->orderBy('paid_at')
            ->get();
    }

    private function periodLabel(int $year, int $month): string
    {
        return Carbon::create($year, $month, 1)->locale('it')->translatedFormat('F Y');
    }

    /**
     * Costruisce il payload per il "Report mensile" (PDF partner):
     * KPI con confronto anno precedente, aggregazione per prodotto+variante,
     * trend ultimi 6 mesi, distribuzione per prodotto.
     */
    public function buildMonthlyReport(Partner $partner, int $year, int $month): array
    {
        $current = $this->buildReport($partner, $year, $month);
        $previous = $this->buildReport($partner, $year - 1, $month);

        $from = CarbonImmutable::create($year, $month, 1, 0, 0, 0);
        $to = $from->endOfMonth();
        $orders = $this->fetchOrders($partner, $from, $to);

        $productRows = $this->aggregateByProductVariant($orders, $partner);
        $distribution = $this->aggregateByProduct($orders);
        $trend = $this->buildMonthlyTrend($partner, $year, $month, 6);

        $kpis = [
            'orders'     => $this->buildKpi((int) $current['orders_count'],       (int) $previous['orders_count']),
            'items'      => $this->buildKpi((int) $current['items_count'],        (int) $previous['items_count']),
            'revenue'    => $this->buildKpi((float) $current['totals']['gross'],       (float) $previous['totals']['gross']),
            'net_margin' => $this->buildKpi((float) $current['totals']['partner_net'], (float) $previous['totals']['partner_net']),
        ];

        return [
            'partner' => $partner,
            'period'  => $current['period'],
            'previous_period_label' => $this->periodLabel($year - 1, $month),
            'kpis'    => $kpis,
            'orders_count' => (int) $current['orders_count'],
            'items_count'  => (int) $current['items_count'],
            'totals'       => $current['totals'],
            'product_rows' => $productRows,
            'product_distribution' => $distribution,
            'monthly_trend' => $trend,
        ];
    }

    /**
     * @return array{current:int|float,previous:int|float,delta_pct:?float,direction:'up'|'down'|'flat'|'none'}
     */
    private function buildKpi(int|float $current, int|float $previous): array
    {
        if ($previous <= 0) {
            return [
                'current'   => $current,
                'previous'  => $previous,
                'delta_pct' => null,
                'direction' => 'none',
            ];
        }

        $deltaPct = (($current - $previous) / $previous) * 100.0;
        $direction = $deltaPct > 0.5 ? 'up' : ($deltaPct < -0.5 ? 'down' : 'flat');

        return [
            'current'   => $current,
            'previous'  => $previous,
            'delta_pct' => round($deltaPct, 1),
            'direction' => $direction,
        ];
    }

    /**
     * Una riga per combinazione prodotto + variante.
     *
     * @param  Collection<int,Order>  $orders
     * @return array<int,array<string,mixed>>
     */
    private function aggregateByProductVariant(Collection $orders, Partner $partner): array
    {
        $groups = [];

        foreach ($orders as $order) {
            foreach ($order->orderProducts as $op) {
                $productLabel = $op->product?->label ?? '—';
                foreach ($op->items as $item) {
                    $variantLabel = $item->variant?->label ?? '—';
                    $key = ($op->product_id ?? 0).'_'.($item->variant_id ?? 0);
                    $qty = (int) $item->quantity;
                    $unitPrice = (float) $item->unit_price;
                    $fixedRate = (float) ($item->partner_commission_miticko_fixed ?? $partner->commission_miticko_fixed ?? 0);
                    $varRate   = (float) ($item->partner_commission_miticko_variable ?? $partner->commission_miticko_variable ?? 0);
                    $commissionUnit = $fixedRate + ($unitPrice * $varRate / 100.0);
                    $marginUnit = $unitPrice - $commissionUnit;

                    if (! isset($groups[$key])) {
                        $groups[$key] = [
                            'product'           => $productLabel,
                            'variant'           => $variantLabel,
                            'unit_price'        => $unitPrice,
                            'commission_unit'   => $commissionUnit,
                            'margin_unit'       => $marginUnit,
                            'units'             => 0,
                            'total_gross'       => 0.0,
                            'total_commissions' => 0.0,
                            'total_partner'     => 0.0,
                        ];
                    }

                    $groups[$key]['units']             += $qty;
                    $groups[$key]['total_gross']       += $unitPrice * $qty;
                    $groups[$key]['total_commissions'] += $commissionUnit * $qty;
                    $groups[$key]['total_partner']     += $marginUnit * $qty;
                }
            }
        }

        foreach ($groups as &$g) {
            $g['total_gross']       = round($g['total_gross'], 2);
            $g['total_commissions'] = round($g['total_commissions'], 2);
            $g['total_partner']     = round($g['total_partner'], 2);
            $g['unit_price']        = round($g['unit_price'], 2);
            $g['commission_unit']   = round($g['commission_unit'], 2);
            $g['margin_unit']       = round($g['margin_unit'], 2);
        }

        return array_values($groups);
    }

    /**
     * @param  Collection<int,Order>  $orders
     * @return array<int,array{label:string,units:int}>
     */
    private function aggregateByProduct(Collection $orders): array
    {
        $groups = [];
        foreach ($orders as $order) {
            foreach ($order->orderProducts as $op) {
                $key = $op->product_id ?? 0;
                $groups[$key] ??= ['label' => $op->product?->label ?? '—', 'units' => 0];
                foreach ($op->items as $item) {
                    $groups[$key]['units'] += (int) $item->quantity;
                }
            }
        }

        uasort($groups, fn ($a, $b) => $b['units'] <=> $a['units']);

        return array_values($groups);
    }

    /**
     * Numero di esperienze (biglietti) vendute per mese, per gli N mesi che
     * terminano con ($year, $month) incluso.
     *
     * @return array<int,array{year:int,month:int,label:string,units:int}>
     */
    private function buildMonthlyTrend(Partner $partner, int $year, int $month, int $months): array
    {
        $end = CarbonImmutable::create($year, $month, 1)->endOfMonth();
        $start = CarbonImmutable::create($year, $month, 1)->subMonths($months - 1)->startOfMonth();

        $orders = $this->fetchOrders($partner, $start, $end);

        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $d = $start->addMonths($i);
            $buckets[sprintf('%04d-%02d', $d->year, $d->month)] = [
                'year'  => $d->year,
                'month' => $d->month,
                'label' => Carbon::create($d->year, $d->month, 1)->locale('it')->translatedFormat('M'),
                'units' => 0,
            ];
        }

        foreach ($orders as $order) {
            $paidAt = $order->paid_at ? Carbon::parse($order->paid_at) : null;
            if (! $paidAt) {
                continue;
            }
            $key = sprintf('%04d-%02d', $paidAt->year, $paidAt->month);
            if (! isset($buckets[$key])) {
                continue;
            }
            foreach ($order->orderProducts as $op) {
                foreach ($op->items as $item) {
                    $buckets[$key]['units'] += (int) $item->quantity;
                }
            }
        }

        return array_values($buckets);
    }

    /**
     * Elenco dei periodi (mese/anno) disponibili per il partner, ricavati dagli
     * ordini paid/completed effettivi. Utile per popolare il selettore in UI.
     *
     * @return array<int,array{value:string,label:string,year:int,month:int}>
     */
    public function availablePeriods(Partner $partner): array
    {
        $rows = Order::query()
            ->where('partner_id', $partner->id)
            ->whereIn('order_status', [OrderStatus::PAID->value, OrderStatus::COMPLETED->value])
            ->whereNotNull('paid_at')
            ->selectRaw('YEAR(paid_at) as y, MONTH(paid_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')
            ->orderByDesc('m')
            ->get();

        return $rows->map(fn ($r) => [
            'value' => sprintf('%04d-%02d', $r->y, $r->m),
            'label' => $this->periodLabel((int) $r->y, (int) $r->m),
            'year' => (int) $r->y,
            'month' => (int) $r->m,
        ])->all();
    }
}
