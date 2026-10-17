<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Services\Statistics\RevenueFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function revenue(Request $request): View
    {
        $user = Auth::user();

        $isMitickoAdmin = in_array($user->role, ['god', 'admin'], true) && $user->partner_id === null;

        $allPartners = $isMitickoAdmin
            ? Partner::orderBy('partner_name')->get(['id', 'partner_name'])
            : collect();

        $selectedPartnerId = $isMitickoAdmin
            ? ($request->integer('partner_id') ?: null)
            : $user->partner_id;

        if ($isMitickoAdmin && $selectedPartnerId && ! $allPartners->contains('id', $selectedPartnerId)) {
            $selectedPartnerId = null;
        }

        $noPartnerContext = $isMitickoAdmin && ! $selectedPartnerId;

        $filters = RevenueFilters::fromRequest($request);

        $kpi = $this->computeKpi($user, $isMitickoAdmin, $selectedPartnerId, $filters, $filters->from, $filters->to);
        $kpiPrev = null;
        $comparisonRange = $filters->comparisonRange();
        if ($comparisonRange && ! $noPartnerContext) {
            [$prevFrom, $prevTo] = $comparisonRange;
            $kpiPrev = $this->computeKpi($user, $isMitickoAdmin, $selectedPartnerId, $filters, $prevFrom, $prevTo);
        }

        $series = $noPartnerContext
            ? ['labels' => [], 'revenue' => [], 'orders' => [], 'visitors' => [], 'prev_revenue' => [], 'prev_orders' => [], 'prev_visitors' => []]
            : $this->buildSeries($user, $isMitickoAdmin, $selectedPartnerId, $filters);

        // Opzioni dropdown filtri
        $statusOptions = collect(OrderStatus::cases())->map(fn (OrderStatus $s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ])->values();

        $categoryOptions = Category::query()
            ->orderBy('label')
            ->get(['id', 'label'])
            ->map(fn ($c) => ['value' => (int) $c->id, 'label' => $c->label])
            ->values();

        $productScope = Product::query()->orderBy('label');
        if ($isMitickoAdmin && $selectedPartnerId) {
            $productScope->where('partner_id', $selectedPartnerId);
        } elseif ($user->role === 'company') {
            $productScope->whereHas('partner', fn ($q) => $q->where('company_id', $user->company_id));
        } elseif (in_array($user->role, ['partner', 'admin'], true)) {
            $productScope->where('partner_id', $user->partner_id);
        }
        $productOptions = $productScope->get(['id', 'label'])
            ->map(fn ($p) => ['value' => (int) $p->id, 'label' => $p->label])
            ->values();

        $partnerFilterOptions = $isMitickoAdmin
            ? $allPartners->map(fn ($p) => ['value' => (int) $p->id, 'label' => $p->partner_name])->values()
            : collect();

        // Modale report
        $reportPartners = match (true) {
            $isMitickoAdmin               => $allPartners,
            $user->role === 'company'     => Partner::where('company_id', $user->company_id)->orderBy('partner_name')->get(['id', 'partner_name']),
            default                       => $user->partner ? collect([$user->partner])->map(fn ($p) => (object) ['id' => $p->id, 'partner_name' => $p->partner_name]) : collect(),
        };

        return view('backoffice.statistics.revenue', [
            'active' => 'statistics.revenue',
            'isMitickoAdmin' => $isMitickoAdmin,
            'allPartners' => $allPartners,
            'selectedPartnerId' => $selectedPartnerId,
            'noPartnerContext' => $noPartnerContext,
            'filters' => $filters,
            'kpi' => $kpi,
            'kpiPrev' => $kpiPrev,
            'series' => $series,
            'statusOptions' => $statusOptions,
            'categoryOptions' => $categoryOptions,
            'productOptions' => $productOptions,
            'partnerFilterOptions' => $partnerFilterOptions,
            'reportPartners' => $reportPartners,
            'reportCurrentPartnerId' => $selectedPartnerId,
        ]);
    }

    public function revenueData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isMitickoAdmin = in_array($user->role, ['god', 'admin'], true) && $user->partner_id === null;
        $selectedPartnerId = $isMitickoAdmin
            ? ($request->integer('partner_id') ?: null)
            : $user->partner_id;

        if ($isMitickoAdmin && ! $selectedPartnerId) {
            return response()->json(['draw' => (int) $request->input('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $filters = RevenueFilters::fromRequest($request);
        $query = $this->baseOrdersQuery($user, $isMitickoAdmin, $selectedPartnerId);
        $filters->apply($query);

        $total = (clone $query)->count('orders.id');

        $start = (int) $request->input('start', 0);
        $length = max(1, min(100, (int) $request->input('length', 10)));

        $orders = $query
            ->with(['orderProducts.product.category', 'orderProducts.items'])
            ->orderBy('orders.created_at', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $rows = $orders->map(function (Order $order) {
            $firstOp = $order->orderProducts->first();
            $bookingDate = $firstOp?->booking_date;
            $bookingTime = $firstOp?->booking_time ? substr((string) $firstOp->booking_time, 0, 5) : null;
            $persons = $order->orderProducts->flatMap->items->sum('quantity');

            return [
                'order_number' => '#MTK-'.$order->order_number,
                'customer' => [
                    'name'  => $order->full_name,
                    'phone' => $order->phone,
                ],
                'visit_date' => $bookingDate ? [
                    'date' => Carbon::parse($bookingDate)->translatedFormat('d M Y'),
                    'time' => $bookingTime,
                ] : null,
                'product' => [
                    'label'    => $firstOp?->product?->label,
                    'category' => $firstOp?->product?->category?->label,
                ],
                'persons' => (int) $persons,
                'order_date' => [
                    'date' => optional($order->created_at)->translatedFormat('d/m/Y'),
                    'time' => optional($order->created_at)->format('H:i'),
                ],
                'customer_status' => $order->customer_status ? [
                    'label'      => $order->customer_status->label(),
                    'appearance' => $order->customer_status->status(),
                    'icon'       => $order->customer_status->icon(),
                ] : null,
                'order_status' => $order->order_status ? [
                    'label'      => $order->order_status->label(),
                    'appearance' => $order->order_status->status(),
                    'icon'       => $order->order_status->icon(),
                ] : null,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $rows,
        ]);
    }

    public function revenueExport(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $isMitickoAdmin = in_array($user->role, ['god', 'admin'], true) && $user->partner_id === null;
        $selectedPartnerId = $isMitickoAdmin
            ? ($request->integer('partner_id') ?: null)
            : $user->partner_id;

        $filters = RevenueFilters::fromRequest($request);
        $query = $this->baseOrdersQuery($user, $isMitickoAdmin, $selectedPartnerId);
        $filters->apply($query);

        $orders = $query
            ->with(['orderProducts.product.category', 'orderProducts.items'])
            ->orderBy('orders.created_at', 'desc')
            ->get();

        $filename = 'fatturato_'.$filters->from->format('Ymd').'-'.$filters->to->format('Ymd').'.csv';

        $callback = function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 per Excel
            fputcsv($out, [
                'Ordine', 'Cliente', 'Telefono', 'Email',
                'Data visita', 'Ora visita', 'Prodotto', 'Categoria',
                'Persone', 'Data ordine', 'Importo', 'Stato cliente', 'Stato ordine',
            ], ';');

            foreach ($orders as $order) {
                $firstOp = $order->orderProducts->first();
                $bookingDate = $firstOp?->booking_date;
                $persons = $order->orderProducts->flatMap->items->sum('quantity');

                fputcsv($out, [
                    '#MTK-'.$order->order_number,
                    $order->full_name,
                    $order->phone,
                    $order->email,
                    $bookingDate ? Carbon::parse($bookingDate)->format('d/m/Y') : '',
                    $firstOp?->booking_time ? substr((string) $firstOp->booking_time, 0, 5) : '',
                    $firstOp?->product?->label ?? '',
                    $firstOp?->product?->category?->label ?? '',
                    $persons,
                    optional($order->created_at)->format('d/m/Y H:i'),
                    number_format((float) $order->amount, 2, ',', ''),
                    $order->customer_status?->label() ?? '',
                    $order->order_status?->label() ?? '',
                ], ';');
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function orders(): View
    {
        return $this->placeholder('statistics.orders', 'Ordini');
    }

    public function product(): View
    {
        return $this->placeholder('statistics.product', 'Prodotto');
    }

    public function category(): View
    {
        return $this->placeholder('statistics.category', 'Categoria');
    }

    public function partner(): View
    {
        return $this->placeholder('statistics.partner', 'Partner');
    }

    public function customers(): View
    {
        return $this->placeholder('statistics.customers', 'Clienti');
    }

    private function placeholder(string $active, string $sectionTitle): View
    {
        return view('backoffice.statistics.placeholder', [
            'active' => $active,
            'sectionTitle' => $sectionTitle,
        ]);
    }

    private function baseOrdersQuery($user, bool $isMitickoAdmin, ?int $selectedPartnerId): Builder
    {
        $query = Order::query();

        if ($isMitickoAdmin) {
            $query->where('orders.partner_id', $selectedPartnerId ?? 0);
        } elseif ($user->role === 'company') {
            $query->whereHas('partner', fn ($q) => $q->where('company_id', $user->company_id));
        } elseif (in_array($user->role, ['partner', 'admin'], true)) {
            $query->where('orders.partner_id', $user->partner_id);
        }

        return $query;
    }

    /**
     * @return array{revenue: float, net_margin: float, commissions: float, payment_fees: float, presale: float, orders_count: int, visitors_count: int}
     */
    private function computeKpi($user, bool $isMitickoAdmin, ?int $selectedPartnerId, RevenueFilters $filters, Carbon $from, Carbon $to): array
    {
        $base = $this->baseOrdersQuery($user, $isMitickoAdmin, $selectedPartnerId);
        $filters->applyWithRange($base, $from, $to);

        $paidStatuses = array_map(fn (OrderStatus $s) => $s->value, [OrderStatus::PAID, OrderStatus::COMPLETED]);

        $revenue = (float) (clone $base)
            ->whereIn('orders.order_status', $paidStatuses)
            ->sum('orders.amount');

        $ordersCount = (int) (clone $base)->count('orders.id');

        $visitors = (int) (clone $base)
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->join('order_product_items', 'order_product_items.order_product_id', '=', 'order_products.id')
            ->sum('order_product_items.quantity');

        $partner = $isMitickoAdmin
            ? ($selectedPartnerId ? Partner::find($selectedPartnerId) : null)
            : $user->partner;
        $commissionPaymentRate = (float) ($partner?->commission_payment ?? 0);
        $commissionServiceRate = (float) ($partner?->commission_miticko_variable ?? 0);

        return [
            'revenue'        => round($revenue, 2),
            'net_margin'     => round($revenue * (1 - ($commissionPaymentRate + $commissionServiceRate) / 100), 2),
            'commissions'    => round($revenue * $commissionServiceRate / 100, 2),
            'payment_fees'   => round($revenue * $commissionPaymentRate / 100, 2),
            'presale'        => 0.0,
            'orders_count'   => $ordersCount,
            'visitors_count' => $visitors,
        ];
    }

    /**
     * @return array{labels: array<int, string>, revenue: array<int, float>, orders: array<int, int>, visitors: array<int, int>, prev_revenue: array<int, float>, prev_orders: array<int, int>, prev_visitors: array<int, int>}
     */
    private function buildSeries($user, bool $isMitickoAdmin, ?int $selectedPartnerId, RevenueFilters $filters): array
    {
        $labels = [];
        $cursor = $filters->from->copy()->startOfDay();
        $endDay = $filters->to->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($endDay)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        [$revenue, $orders, $visitors] = $this->seriesForRange($user, $isMitickoAdmin, $selectedPartnerId, $filters, $filters->from, $filters->to, $labels);

        $prevRevenue = $prevOrders = $prevVisitors = array_fill(0, count($labels), 0);
        if ($range = $filters->comparisonRange()) {
            [$pFrom, $pTo] = $range;
            $pLabels = [];
            $c = $pFrom->copy()->startOfDay();
            $pEnd = $pTo->copy()->startOfDay();
            while ($c->lessThanOrEqualTo($pEnd)) {
                $pLabels[] = $c->toDateString();
                $c->addDay();
            }
            [$prevRevenue, $prevOrders, $prevVisitors] = $this->seriesForRange($user, $isMitickoAdmin, $selectedPartnerId, $filters, $pFrom, $pTo, $pLabels);
            // Allineo lunghezza al periodo corrente (per asse X comune)
            $prevRevenue = array_slice(array_pad($prevRevenue, count($labels), 0), 0, count($labels));
            $prevOrders  = array_slice(array_pad($prevOrders,  count($labels), 0), 0, count($labels));
            $prevVisitors= array_slice(array_pad($prevVisitors,count($labels), 0), 0, count($labels));
        }

        return [
            'labels'        => $labels,
            'revenue'       => $revenue,
            'orders'        => $orders,
            'visitors'      => $visitors,
            'prev_revenue'  => $prevRevenue,
            'prev_orders'   => $prevOrders,
            'prev_visitors' => $prevVisitors,
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @return array{0: array<int, float>, 1: array<int, int>, 2: array<int, int>}
     */
    private function seriesForRange($user, bool $isMitickoAdmin, ?int $selectedPartnerId, RevenueFilters $filters, Carbon $from, Carbon $to, array $labels): array
    {
        $base = $this->baseOrdersQuery($user, $isMitickoAdmin, $selectedPartnerId);
        $filters->applyWithRange($base, $from, $to);

        $paidStatuses = array_map(fn (OrderStatus $s) => $s->value, [OrderStatus::PAID, OrderStatus::COMPLETED]);
        $isVisitDate = $filters->source === RevenueFilters::SOURCE_VISIT_DATE;
        $dateExpr = $isVisitDate ? 'order_products.booking_date' : 'DATE(orders.created_at)';

        $buildAggregate = function (string $selectExpr, bool $joinOrderProducts, bool $joinItems = false) use ($base, $isVisitDate) {
            $q = (clone $base)->toBase();
            $q->columns = null;
            if ($joinOrderProducts || $isVisitDate) {
                $q->join('order_products', 'order_products.order_id', '=', 'orders.id');
            }
            if ($joinItems) {
                $q->join('order_product_items', 'order_product_items.order_product_id', '=', 'order_products.id');
            }
            return $q->selectRaw($selectExpr);
        };

        $revenueByDay = $buildAggregate("$dateExpr as d, SUM(orders.amount) as v", false)
            ->whereIn('orders.order_status', $paidStatuses)
            ->groupBy('d')
            ->pluck('v', 'd');

        $ordersByDay = $buildAggregate("$dateExpr as d, COUNT(DISTINCT orders.id) as v", false)
            ->groupBy('d')
            ->pluck('v', 'd');

        $visitorsByDay = $buildAggregate("$dateExpr as d, SUM(order_product_items.quantity) as v", true, true)
            ->groupBy('d')
            ->pluck('v', 'd');

        $revenueSeries = $ordersSeries = $visitorsSeries = [];
        foreach ($labels as $day) {
            $revenueSeries[] = (float) ($revenueByDay[$day] ?? 0);
            $ordersSeries[]  = (int)   ($ordersByDay[$day] ?? 0);
            $visitorsSeries[]= (int)   ($visitorsByDay[$day] ?? 0);
        }

        return [$revenueSeries, $ordersSeries, $visitorsSeries];
    }
}
