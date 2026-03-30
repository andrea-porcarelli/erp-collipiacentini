<?php

namespace App\Services;

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
            ->where('date', $date)
            ->orderBy('time')
            ->get();

        if ($specials->isNotEmpty()) {
            return $specials->map(fn($s) => [
                'id'           => $s->id,
                'time'         => substr($s->time, 0, 5),
                'availability' => $s->availability,
                'slot_type'    => 'special',
                'slot_id'      => $s->id,
            ]);
        }

        // Fall back to weekly template
        $dayOfWeek = Carbon::parse($date)->isoWeekday(); // 1=Mon...7=Sun
        $weekly = ProductAvailability::where('product_id', $product->id)
            ->where('day_of_week', $dayOfWeek)
            ->whereNotNull('day_of_week')
            ->orderBy('time')
            ->get();

        return $weekly->map(fn($s) => [
            'id'           => $s->id,
            'time'         => substr($s->time, 0, 5),
            'availability' => $s->availability,
            'slot_type'    => 'weekly',
            'slot_id'      => $s->id,
        ]);
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
     * Decrement availability for a slot after booking.
     */
    public function decrementSlot(string $slotType, int $slotId, int $quantity): void
    {
        if ($slotType === 'special') {
            ProductSpecialSchedule::where('id', $slotId)
                ->whereNotNull('availability')
                ->decrement('availability', $quantity);
        } else {
            ProductAvailability::where('id', $slotId)
                ->whereNotNull('availability')
                ->decrement('availability', $quantity);
        }
    }

    /**
     * Restore availability for a slot (e.g. after failed payment).
     */
    public function restoreSlot(string $slotType, int $slotId, int $quantity): void
    {
        if ($slotType === 'special') {
            ProductSpecialSchedule::where('id', $slotId)->increment('availability', $quantity);
        } else {
            ProductAvailability::where('id', $slotId)->increment('availability', $quantity);
        }
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

        // Days with special overrides that have availability
        $specialDays = ProductSpecialSchedule::where('product_id', $product->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(fn($q) => $q->whereNull('availability')->orWhere('availability', '>', 0))
            ->distinct()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Days that are fully overridden (all special slots have availability=0): exclude them
        $closedSpecialDays = ProductSpecialSchedule::where('product_id', $product->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(date) as day, SUM(COALESCE(availability, 1)) as total_avail')
            ->groupBy('day')
            ->havingRaw('total_avail = 0')
            ->pluck('day')
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
