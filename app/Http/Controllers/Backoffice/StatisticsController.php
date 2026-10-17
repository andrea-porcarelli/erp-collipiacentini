<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Partner;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(Request $request): View
    {
        $user = Auth::user();

        // Admin Miticko = god o admin senza partner assegnato: devono selezionare un Partner.
        $isMitickoAdmin = in_array($user->role, ['god', 'admin'], true) && $user->partner_id === null;

        $allPartners = $isMitickoAdmin
            ? Partner::orderBy('partner_name')->get(['id', 'partner_name'])
            : collect();

        $selectedPartnerId = $isMitickoAdmin
            ? ($request->integer('partner_id') ?: null)
            : $user->partner_id;

        // Verifica che il partner_id dalla query appartenga davvero alla lista disponibile.
        if ($isMitickoAdmin && $selectedPartnerId && ! $allPartners->contains('id', $selectedPartnerId)) {
            $selectedPartnerId = null;
        }

        $from = Carbon::now()->subDays(30)->startOfDay();
        $to = Carbon::now()->endOfDay();

        // Senza partner selezionato (admin Miticko in attesa di scelta) → dati vuoti, UI bloccata.
        $noPartnerContext = $isMitickoAdmin && ! $selectedPartnerId;

        $ordersQuery = Order::query()->whereBetween('created_at', [$from, $to]);

        if ($isMitickoAdmin) {
            $ordersQuery->where('partner_id', $selectedPartnerId ?? 0);
        } elseif ($user->role === 'company') {
            $ordersQuery->whereHas('partner', fn ($q) => $q->where('company_id', $user->company_id));
        } elseif (in_array($user->role, ['partner', 'admin'], true)) {
            $ordersQuery->where('partner_id', $user->partner_id);
        }

        $paidStatuses = [OrderStatus::PAID, OrderStatus::COMPLETED];

        $revenue = (clone $ordersQuery)
            ->whereIn('order_status', array_map(fn ($s) => $s->value, $paidStatuses))
            ->sum('amount');

        $partner = $isMitickoAdmin
            ? ($selectedPartnerId ? Partner::find($selectedPartnerId) : null)
            : $user->partner;
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

        $uniqueCustomers = (int) (clone $ordersQuery)->whereNotNull('email')->distinct('email')->count('email');
        $ordersCount = (int) (clone $ordersQuery)->count();
        $recurringCustomers = (int) (clone $ordersQuery)
            ->whereNotNull('email')
            ->selectRaw('email, COUNT(*) as c')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

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

        // Opzioni per la modale "Genera report"
        $reportPartners = match (true) {
            $isMitickoAdmin               => $allPartners,
            $user->role === 'company'     => Partner::where('company_id', $user->company_id)->orderBy('partner_name')->get(['id', 'partner_name']),
            default                       => $user->partner ? collect([$user->partner])->map(fn ($p) => (object) ['id' => $p->id, 'partner_name' => $p->partner_name]) : collect(),
        };
        $reportCurrentPartnerId = $selectedPartnerId;

        return view('backoffice.statistics.index', [
            'active' => 'statistics',
            'isMitickoAdmin' => $isMitickoAdmin,
            'allPartners' => $allPartners,
            'selectedPartnerId' => $selectedPartnerId,
            'noPartnerContext' => $noPartnerContext,
            'from' => $from,
            'to' => $to,
            'reportPartners' => $reportPartners,
            'reportCurrentPartnerId' => $reportCurrentPartnerId,
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
