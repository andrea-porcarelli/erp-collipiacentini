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
            ->where('amount', '>', 0)
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
            ->where('amount', '>', 0)
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
