<?php

namespace App\Services;

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
        $occupancyFallback = (int) $product->occupancy ?: null;
        $cutoff = $this->bookingCutoff($product);

        $specials = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->orderBy('time')
            ->get();

        $overrides = $specials->where('is_disabled', false);
        $blacklist = $specials->where('is_disabled', true)
            ->map(fn ($s) => substr($s->time, 0, 5))
            ->values()
            ->all();

        if ($overrides->isNotEmpty()) {
            $overrides->load(['variants', 'generic_variants']);

            return $overrides->map(function ($s) use ($date, $occupancyFallback) {
                $maxQty = $s->variants->count()
                    ? $s->variants->max('max_quantity')
                    : $s->generic_variants->max('max_quantity');
                $maxQty = $maxQty ?? $occupancyFallback;
                $booked = $this->getBookedQuantity('special', $s->id, $date);

                return [
                    'id' => $s->id,
                    'time' => substr($s->time, 0, 5),
                    'availability' => is_null($maxQty) ? null : max(0, $maxQty - $booked),
                    'slot_type' => 'special',
                    'slot_id' => $s->id,
                ];
            })->filter(fn ($slot) => $this->slotIsBookable($date, $slot['time'], $cutoff))->values();
        }

        // Fall back to weekly template
        $dayOfWeek = Carbon::parse($date)->isoWeekday(); // 1=Mon...7=Sun
        $weekly = ProductAvailability::where('product_id', $product->id)
            ->with(['variants', 'generic_variants'])
            ->where('day_of_week', $dayOfWeek)
            ->whereNotNull('day_of_week')
            ->orderBy('time')
            ->get();

        return $weekly->map(function ($s) use ($date, $occupancyFallback) {
            $maxQty = $s->variants->count()
                ? $s->variants->max('max_quantity')
                : $s->generic_variants->max('max_quantity');
            $maxQty = $maxQty ?? $occupancyFallback;
            $booked = $this->getBookedQuantity('weekly', $s->id, $date);

            return [
                'id' => $s->id,
                'time' => substr($s->time, 0, 5),
                'availability' => is_null($maxQty) ? null : max(0, $maxQty - $booked),
                'slot_type' => 'weekly',
                'slot_id' => $s->id,
            ];
        })
            ->filter(fn ($slot) => ! in_array($slot['time'], $blacklist, true))
            ->filter(fn ($slot) => $this->slotIsBookable($date, $slot['time'], $cutoff))
            ->values();
    }

    /**
     * Earliest datetime that is still bookable, given the product's deadline.
     */
    private function bookingCutoff(Product $product): Carbon
    {
        $hours = (int) ($product->booking_deadline_hours ?? 0);

        return Carbon::now()->addHours($hours);
    }

    /**
     * Returns true when the slot (date + HH:mm) is still bookable relative to the cutoff.
     */
    private function slotIsBookable(string $date, string $time, Carbon $cutoff): bool
    {
        $slotAt = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time);

        return $slotAt->greaterThanOrEqualTo($cutoff);
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
            ->first(fn ($s) => $s['time'] === substr($time, 0, 5));
    }

    /**
     * Count confirmed bookings for a slot on a given date.
     * Excludes failed, cancelled, and refunded orders.
     */
    public function getBookedQuantity(string $slotType, int $slotId, string $date): int
    {
        return (int) OrderProduct::whereHas('order', fn ($q) => $q->whereNotIn('order_status', ['failed', 'cancelled', 'refunded']))
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
        if (! $variation) {
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
        $end = $start->copy()->endOfMonth();

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
            ->where('is_disabled', false)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($hasGenericVariants, $variantHasCapacity) {
                $q->whereHas('variants', $variantHasCapacity);
                if ($hasGenericVariants) {
                    $q->orWhereDoesntHave('variants');
                }
            })
            ->distinct()
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        // Days that are fully overridden (no variant with capacity and no generic fallback): exclude them
        $closedSpecialDays = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('is_disabled', false)
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
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        // Weekly template slots indexed by day_of_week (only times)
        $templateSlotsByWeekday = ProductAvailability::where('product_id', $product->id)
            ->whereNotNull('day_of_week')
            ->get(['day_of_week', 'time'])
            ->groupBy('day_of_week')
            ->map(fn ($rows) => $rows->map(fn ($r) => substr($r->time, 0, 5))->values()->all())
            ->toArray();
        $activeWeekdays = array_keys($templateSlotsByWeekday);

        // Blacklist (is_disabled=true) per data, raggruppata
        $blacklistByDate = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('is_disabled', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'time'])
            ->groupBy(fn ($r) => $r->date->format('Y-m-d'))
            ->map(fn ($rows) => $rows->map(fn ($r) => substr($r->time, 0, 5))->values()->all())
            ->toArray();

        // Dates with a real override (is_disabled=false): the weekly template does NOT apply.
        $overrideDates = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('is_disabled', false)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        $available = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dateStr = $current->toDateString();

            // Skip closed periods
            $isClosed = $closedPeriods->contains(
                fn ($p) => $p->date_from->toDateString() <= $dateStr && $p->date_to->toDateString() >= $dateStr
            );

            if (! $isClosed) {
                if (in_array($dateStr, $specialDays) && ! in_array($dateStr, $closedSpecialDays)) {
                    $available[] = $dateStr;
                } elseif (! in_array($dateStr, $closedSpecialDays) && ! in_array($dateStr, $overrideDates) && in_array($current->isoWeekday(), $activeWeekdays)) {
                    // Weekly template applies: subtract blacklisted times for this date.
                    $templateTimes = $templateSlotsByWeekday[$current->isoWeekday()] ?? [];
                    $blacklistedTimes = $blacklistByDate[$dateStr] ?? [];
                    $remaining = array_diff($templateTimes, $blacklistedTimes);
                    if (! empty($remaining)) {
                        $available[] = $dateStr;
                    }
                }
            }

            $current->addDay();
        }

        return $available;
    }
}
