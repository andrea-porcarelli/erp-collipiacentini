<?php

namespace App\Repositories;

use App\Interfaces\CalendarInterface;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarRepository implements CalendarInterface
{
    public function __construct(private ProductAvailabilityService $availability)
    {
    }

    public function weekOverview(int $partnerId, Carbon $weekStart): Collection
    {
        $weekStart = $weekStart->copy()->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6);

        // Un pallino sotto il numero del giorno = almeno una prenotazione attiva
        // (esclude cancellati/rimborsati/falliti per non far comparire giorni "fantasma").
        $counts = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.partner_id', $partnerId)
            ->whereBetween('order_products.booking_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereNotIn('orders.order_status', ['failed', 'cancelled', 'refunded'])
            ->groupBy('order_products.booking_date')
            ->selectRaw('order_products.booking_date, COUNT(DISTINCT order_products.order_id) as orders_count')
            ->pluck('orders_count', 'booking_date');

        return collect(range(0, 6))->map(function ($offset) use ($weekStart, $counts) {
            $date = $weekStart->copy()->addDays($offset)->toDateString();
            $count = (int) ($counts[$date] ?? 0);

            return [
                'date'         => $date,
                'orders_count' => $count,
                'has_bookings' => $count > 0,
            ];
        });
    }

    public function daySlots(int $partnerId, string $date, string $groupBy): Collection
    {
        $products = Product::where('partner_id', $partnerId)
            ->where('is_active', 1)
            ->orderBy('label')
            ->get();

        $productsWithSlots = $products->map(function (Product $product) use ($date) {
            $slots = $this->availability->getSlotsForDate($product, $date);

            return [
                'product' => $product,
                'slots'   => $slots,
            ];
        });

        $bookedAggregates = $this->bookedAggregatesForDay($partnerId, $date);

        // Costruiamo l'elenco slot completo (template + special), poi lo arricchiamo con i booked count.
        $enrichedProducts = $productsWithSlots->map(function ($entry) use ($bookedAggregates) {
            $product = $entry['product'];
            $productBookings = $bookedAggregates->get($product->id, collect());

            $slots = $entry['slots']->map(function ($slot) use ($productBookings) {
                $stat = $productBookings->get($slot['time']);
                $booked = $stat['booked'] ?? 0;
                $orders = $stat['orders'] ?? 0;
                $availability = $slot['availability'];
                $capacity = is_null($availability) ? null : ($availability + $booked);

                return [
                    'time'         => $slot['time'],
                    'slot_type'    => $slot['slot_type'],
                    'slot_id'      => $slot['slot_id'],
                    'availability' => $availability,
                    'booked'       => $booked,
                    'orders_count' => $orders,
                    'capacity'     => $capacity,
                ];
            })->values();

            // Aggiungiamo anche eventuali slot che hanno prenotazioni ma non compaiono nel template
            // (es. slot di special-schedule già passato o slot template modificato dopo l'ordine).
            $templateTimes = $slots->pluck('time')->all();
            $orphans = $productBookings->reject(fn ($stat, $time) => in_array($time, $templateTimes, true))
                ->map(fn ($stat, $time) => [
                    'time'         => $time,
                    'slot_type'    => $stat['slot_type'] ?? 'weekly',
                    'slot_id'      => $stat['slot_id'] ?? 0,
                    'availability' => null,
                    'booked'       => $stat['booked'] ?? 0,
                    'orders_count' => $stat['orders'] ?? 0,
                    'capacity'     => null,
                ])->values();

            return [
                'product' => $product,
                'slots'   => $slots->merge($orphans)->sortBy('time')->values(),
            ];
        })->filter(fn ($entry) => $entry['slots']->isNotEmpty())->values();

        if ($groupBy === 'slot') {
            return $this->pivotByTime($enrichedProducts);
        }

        return $enrichedProducts;
    }

    public function slotOrders(int $partnerId, int $productId, string $date, string $time, array $filters): Collection
    {
        $normalizedTime = substr($time, 0, 5);

        $orders = Order::query()
            ->where('partner_id', $partnerId)
            ->whereHas('orderProducts', function ($q) use ($productId, $date, $normalizedTime) {
                $q->where('product_id', $productId)
                    ->where('booking_date', $date)
                    ->whereRaw('SUBSTRING(booking_time, 1, 5) = ?', [$normalizedTime]);
            })
            ->with([
                'customer',
                'orderProducts.items.variant',
                'orderProducts.product',
                'participants',
            ])
            ->orderBy('created_at')
            ->get();

        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $orders = $orders->where('order_status', $filters['order_status'])->values();
        }

        if (! empty($filters['check_in']) && $filters['check_in'] !== 'all') {
            // Mostra ordini con almeno un partecipante nello stato selezionato
            // (booked, checked_in, no_show, refunded, cancelled).
            $orders = $orders->filter(function (Order $order) use ($filters) {
                return $order->participants
                    ->contains(fn ($p) => $p->status === $filters['check_in']);
            })->values();
        }

        if (! empty($filters['search'])) {
            $q = mb_strtolower(trim($filters['search']));
            $orders = $orders->filter(function (Order $order) use ($q) {
                $name = mb_strtolower(($order->customer->name ?? '').' '.($order->customer->surname ?? ''));
                $number = mb_strtolower($order->order_number ?? '');

                return str_contains($name, $q) || str_contains($number, $q);
            })->values();
        }

        return $orders->map(function (Order $order) {
            $summary = $this->checkinSummary($order);

            return [
                'order'    => $order,
                'checkin'  => $summary,
            ];
        });
    }

    /**
     * Aggrega, per un partner nel giorno indicato, orders_count e booked (partecipanti attivi)
     * per ogni combinazione product_id + booking_time.
     * Ritorna: Collection keyed by product_id → Collection keyed by 'HH:MM' → ['booked','orders','slot_type','slot_id'].
     */
    private function bookedAggregatesForDay(int $partnerId, string $date): Collection
    {
        $rows = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.partner_id', $partnerId)
            ->where('order_products.booking_date', $date)
            ->whereNotIn('orders.order_status', ['failed', 'cancelled', 'refunded'])
            ->with(['items.participants', 'product:id'])
            ->select('order_products.*')
            ->get();

        $out = collect();
        foreach ($rows as $op) {
            $time = substr($op->booking_time ?? '', 0, 5);
            if ($time === '') {
                continue;
            }

            $participants = $op->items->flatMap->participants;
            $booked = $participants->isEmpty()
                ? (int) $op->quantity
                : $participants->whereNotIn('status', ['cancelled', 'refunded'])->count();

            $productBucket = $out->get($op->product_id, collect());
            $current = $productBucket->get($time, [
                'booked'    => 0,
                'orders'    => 0,
                'orders_ids' => [],
                'slot_type' => $op->slot_type,
                'slot_id'   => $op->slot_id,
            ]);
            $current['booked'] += $booked;
            if (! in_array($op->order_id, $current['orders_ids'], true)) {
                $current['orders_ids'][] = $op->order_id;
                $current['orders']++;
            }
            $productBucket->put($time, $current);
            $out->put($op->product_id, $productBucket);
        }

        return $out;
    }

    /**
     * Trasforma la vista "per prodotto" in "per fascia oraria".
     * Restituisce Collection di ['time', 'products' => [...]].
     */
    private function pivotByTime(Collection $enrichedProducts): Collection
    {
        $byTime = collect();
        foreach ($enrichedProducts as $entry) {
            $product = $entry['product'];
            foreach ($entry['slots'] as $slot) {
                $bucket = $byTime->get($slot['time'], ['time' => $slot['time'], 'products' => collect()]);
                $bucket['products']->push([
                    'product'      => $product,
                    'availability' => $slot['availability'],
                    'booked'       => $slot['booked'],
                    'orders_count' => $slot['orders_count'],
                    'capacity'     => $slot['capacity'],
                    'slot_type'    => $slot['slot_type'],
                    'slot_id'      => $slot['slot_id'],
                ]);
                $byTime->put($slot['time'], $bucket);
            }
        }

        return $byTime->sortKeys()->values()->map(function ($bucket) {
            $bucket['products'] = $bucket['products']->sortBy(fn ($p) => $p['product']->label)->values();

            return $bucket;
        });
    }

    /**
     * Conta partecipanti attesi (booked non annullati) e già arrivati (checked_in) per un ordine.
     */
    private function checkinSummary(Order $order): array
    {
        $participants = $order->participants;
        $expected = $participants->whereNotIn('status', ['cancelled', 'refunded'])->count();
        $checkedIn = $participants->where('status', 'checked_in')->count();

        return [
            'expected'   => $expected,
            'checked_in' => $checkedIn,
        ];
    }
}
