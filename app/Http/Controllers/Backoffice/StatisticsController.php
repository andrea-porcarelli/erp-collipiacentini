<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(): View
    {
        $user = Auth::user();

        if ($user->role === 'god') {
            return view('backoffice.statistics.index', [
                'isGod' => true,
                'active' => 'statistics',
            ]);
        }

        $from = Carbon::now()->subDays(30)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $ordersQuery = Order::query()->whereBetween('created_at', [$from, $to]);

        if ($user->role === 'company') {
            $ordersQuery->whereHas('partner', fn ($q) => $q->where('company_id', $user->company_id));
        } elseif (in_array($user->role, ['partner', 'admin'], true)) {
            $ordersQuery->where('partner_id', $user->partner_id);
        }

        $paidStatuses = [OrderStatus::PAID, OrderStatus::COMPLETED];

        $revenue = (clone $ordersQuery)
            ->whereIn('order_status', array_map(fn ($s) => $s->value, $paidStatuses))
            ->sum('amount');

        $partner = $user->partner;
        $commissionPaymentRate = (float) ($partner?->commission_payment ?? 0);
        $commissionServiceRate = (float) ($partner?->commission_miticko_variable ?? 0);

        $netMargin = round((float) $revenue * (1 - ($commissionPaymentRate + $commissionServiceRate) / 100), 2);
        $commissions = round((float) $revenue * $commissionServiceRate / 100, 2);
        $paymentFees = round((float) $revenue * $commissionPaymentRate / 100, 2);
        $presale = 0.0;

        $orders = (clone $ordersQuery)
            ->with(['country', 'orderProducts.product', 'orderProducts.items.variant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Statistiche → Ordini: aggregazione per email (identificativo cliente ai fini statistici).
        $uniqueCustomers = (int) (clone $ordersQuery)->whereNotNull('email')->distinct('email')->count('email');
        $ordersCount = (int) (clone $ordersQuery)->count();
        $recurringCustomers = (int) (clone $ordersQuery)
            ->whereNotNull('email')
            ->selectRaw('email, COUNT(*) as c')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Statistiche → Clienti: anagrafica per singolo ordine (non aggregata per identità).
        $ordersPerCity = (clone $ordersQuery)
            ->whereNotNull('city')
            ->selectRaw('city, COUNT(*) as c')
            ->groupBy('city')
            ->orderByDesc('c')
            ->limit(20)
            ->get();

        $ordersPerCountry = (clone $ordersQuery)
            ->whereNotNull('country_id')
            ->selectRaw('country_id, COUNT(*) as c')
            ->groupBy('country_id')
            ->orderByDesc('c')
            ->limit(20)
            ->get();

        $countriesById = \App\Models\Country::whereIn('id', $ordersPerCountry->pluck('country_id'))
            ->pluck('name', 'id');

        $ordersPerAgeBucket = (clone $ordersQuery)
            ->whereNotNull('birth_date')
            ->selectRaw("
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN '<18'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 55 AND 64 THEN '55-64'
                    ELSE '65+'
                END AS bucket,
                COUNT(*) as c
            ")
            ->groupBy('bucket')
            ->get()
            ->pluck('c', 'bucket');

        $ageBucketOrder = ['<18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
        $ordersPerAge = collect($ageBucketOrder)
            ->map(fn ($b) => ['bucket' => $b, 'count' => (int) ($ordersPerAgeBucket[$b] ?? 0)])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();

        $ordersWithoutAge = (int) (clone $ordersQuery)->whereNull('birth_date')->count();

        return view('backoffice.statistics.index', [
            'isGod' => false,
            'active' => 'statistics',
            'from' => $from,
            'to' => $to,
            'kpi' => [
                'revenue' => (float) $revenue,
                'net_margin' => $netMargin,
                'commissions' => $commissions,
                'payment_fees' => $paymentFees,
                'presale' => $presale,
                'unique_customers' => $uniqueCustomers,
                'orders_count' => $ordersCount,
                'recurring_customers' => $recurringCustomers,
            ],
            'orders' => $orders,
            'orders_per_city' => $ordersPerCity,
            'orders_per_country' => $ordersPerCountry,
            'countries_by_id' => $countriesById,
            'orders_per_age' => $ordersPerAge,
            'orders_without_age' => $ordersWithoutAge,
        ]);
    }
}
