<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\ProductClosedPeriod;
use App\Models\ProductPriceVariation;
use App\Models\ProductSpecialSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductAvailabilityService
{
    /**
     * Check if a date falls within any closed period for the product.
     */
    public function isDateClosed(Product $product, string $date): bool
    {
        return ProductClosedPeriod::where('product_id', $product->id)
            ->where('date_from', '<=', $date)
            ->where('date_to', '>=', $date)
            ->exists();
    }

    /**
     * Get available slots for a given date.
     * Returns special overrides if they exist, otherwise falls back to the weekly template.
     * Each item: ['id', 'time', 'availability', 'slot_type', 'slot_id']
     */
    public function getSlotsForDate(Product $product, string $date): Collection
    {
        $specials = ProductSpecialSchedule::where('product_id', $product->id)
            ->with(['variants', 'generic_variants'])
            ->where('date', $date)
            ->orderBy('time')
            ->get();

        if ($specials->isNotEmpty()) {
            return $specials->map(function ($s) use ($date) {
                $maxQty = $s->variants->count()
                    ? $s->variants->max('max_quantity')
                    : $s->generic_variants->max('max_quantity');
                $booked = $this->getBookedQuantity('special', $s->id, $date);
                return [
                    'id'           => $s->id,
                    'time'         => substr($s->time, 0, 5),
                    'availability' => is_null($maxQty) ? null : max(0, $maxQty - $booked),
                    'slot_type'    => 'special',
                    'slot_id'      => $s->id,
                ];
            });
        }

        // Fall back to weekly template
        $dayOfWeek = Carbon::parse($date)->isoWeekday(); // 1=Mon...7=Sun
        $weekly = ProductAvailability::where('product_id', $product->id)
            ->with(['variants', 'generic_variants'])
            ->where('day_of_week', $dayOfWeek)
            ->whereNotNull('day_of_week')
            ->orderBy('time')
            ->get();

        return $weekly->map(function ($s) use ($date) {
            $maxQty = $s->variants->count()
                ? $s->variants->max('max_quantity')
                : $s->generic_variants->max('max_quantity');
            $booked = $this->getBookedQuantity('weekly', $s->id, $date);
            return [
                'id'           => $s->id,
                'time'         => substr($s->time, 0, 5),
                'availability' => is_null($maxQty) ? null : max(0, $maxQty - $booked),
                'slot_type'    => 'weekly',
                'slot_id'      => $s->id,
            ];
        });
    }

    /**
     * Find a single slot matching date + time.
     * Returns null if not found or date is closed.
     */
    public function getSlot(Product $product, string $date, string $time): ?array
    {
        if ($this->isDateClosed($product, $date)) {
            return null;
        }

        return $this->getSlotsForDate($product, $date)
            ->first(fn($s) => $s['time'] === substr($time, 0, 5));
    }

    /**
     * Count confirmed bookings for a slot on a given date.
     * Excludes failed, cancelled, and refunded orders.
     */
    public function getBookedQuantity(string $slotType, int $slotId, string $date): int
    {
        return (int) OrderProduct::whereHas('order', fn($q) => $q->whereNotIn('order_status', ['failed', 'cancelled', 'refunded']))
            ->where('slot_type', $slotType)
            ->where('slot_id', $slotId)
            ->where('booking_date', $date)
            ->sum('quantity');
    }

    /**
     * Find the first price variation that covers the given date.
     */
    public function getApplicablePriceVariation(Product $product, string $date): ?ProductPriceVariation
    {
        return ProductPriceVariation::where('product_id', $product->id)
            ->where('date_from', '<=', $date)
            ->where('date_to', '>=', $date)
            ->first();
    }

    /**
     * Apply a price variation to a base price.
     */
    public function applyPriceVariation(float $basePrice, ?ProductPriceVariation $variation): float
    {
        if (!$variation) {
            return $basePrice;
        }

        $delta = $variation->unit === 'percent'
            ? $basePrice * ($variation->value / 100)
            : (float) $variation->value;

        $result = $variation->direction === 'decrement'
            ? $basePrice - $delta
            : $basePrice + $delta;

        return max(0, round($result, 2));
    }

    /**
     * Get days with at least one available slot in a given month.
     * Returns array of 'Y-m-d' strings.
     */
    public function getAvailableDaysForMonth(Product $product, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        $closedPeriods = ProductClosedPeriod::where('product_id', $product->id)
            ->where('date_from', '<=', $end->toDateString())
            ->where('date_to', '>=', $start->toDateString())
            ->get();

        // Una variante con max_quantity NULL eredita la capienza dal product.occupancy:
        // quindi è "disponibile" se max_quantity > 0 OPPURE NULL (con product.occupancy > 0).
        $hasOccupancyFallback = (int) $product->occupancy > 0;
        $variantHasCapacity = function ($qq) use ($hasOccupancyFallback) {
            $qq->where(function ($q) use ($hasOccupancyFallback) {
                $q->where('max_quantity', '>', 0);
                if ($hasOccupancyFallback) {
                    $q->orWhereNull('max_quantity');
                }
            });
        };

        // Product-level default variants act as fallback when a special schedule
        // has no variants of its own (availability_id = NULL AND special_schedule_id = NULL).
        $hasGenericVariants = $product->variants()
            ->whereNull('availability_id')
            ->whereNull('special_schedule_id')
            ->where(function ($q) use ($hasOccupancyFallback) {
                $q->where('max_quantity', '>', 0);
                if ($hasOccupancyFallback) {
                    $q->orWhereNull('max_quantity');
                }
            })
            ->exists();

        // Days with special overrides that have availability (own variants or inherited defaults)
        $specialDays = ProductSpecialSchedule::where('product_id', $product->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($hasGenericVariants, $variantHasCapacity) {
                $q->whereHas('variants', $variantHasCapacity);
                if ($hasGenericVariants) {
                    $q->orWhereDoesntHave('variants');
                }
            })
            ->distinct()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Days that are fully overridden (no variant with capacity and no generic fallback): exclude them
        $closedSpecialDays = ProductSpecialSchedule::where('product_id', $product->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($hasGenericVariants, $variantHasCapacity) {
                if ($hasGenericVariants) {
                    $q->whereHas('variants')
                      ->whereDoesntHave('variants', $variantHasCapacity);
                } else {
                    $q->whereDoesntHave('variants', $variantHasCapacity);
                }
            })
            ->distinct()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Weekly template: which day_of_week values have any slot
        $activeWeekdays = ProductAvailability::where('product_id', $product->id)
            ->whereNotNull('day_of_week')
            ->distinct()
            ->pluck('day_of_week')
            ->toArray();

        $available = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dateStr = $current->toDateString();

            // Skip closed periods
            $isClosed = $closedPeriods->contains(
                fn($p) => $p->date_from->toDateString() <= $dateStr && $p->date_to->toDateString() >= $dateStr
            );

            if (!$isClosed) {
                if (in_array($dateStr, $specialDays) && !in_array($dateStr, $closedSpecialDays)) {
                    $available[] = $dateStr;
                } elseif (!in_array($dateStr, $closedSpecialDays) && in_array($current->isoWeekday(), $activeWeekdays)) {
                    // Weekly template applies — check if this specific date has a full override (that blocked it)
                    $hasSpecialOverride = ProductSpecialSchedule::where('product_id', $product->id)
                        ->where('date', $dateStr)
                        ->exists();

                    if (!$hasSpecialOverride) {
                        $available[] = $dateStr;
                    }
                }
            }

            $current->addDay();
        }

        return $available;
    }
}
