<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DateAvailabilityResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     *
     * Expects a grouped collection item with keys:
     * - date: string
     * - availabilities: Collection of ProductAvailability
     * - price: float|null
     */
    public function toArray(Request $request): array
    {
        $availabilities = $this['availabilities'];
        $hasTimeSlots = $availabilities->whereNotNull('time')->where('time', '!=', '')->isNotEmpty();
        $price = $this['price'] ?? null;

        if ($hasTimeSlots) {
            $timeSlots = $availabilities
                ->whereNotNull('time')
                ->where('time', '!=', '')
                ->map(fn($avail) => [
                    'start' => $avail->time,
                    'available_slots' => (int) $avail->availability,
                    'max_capacity' => (int) $avail->availability,
                ])
                ->values()
                ->toArray();

            $anyAvailable = $availabilities->sum('availability') > 0;

            return [
                'available' => $anyAvailable,
                'available_slots' => (int) $availabilities->sum('availability'),
                'max_capacity' => (int) $availabilities->sum('availability'),
                'price' => $price,
                'time_slots' => $timeSlots,
            ];
        }

        $totalAvailability = (int) $availabilities->sum('availability');

        return [
            'available' => $totalAvailability > 0,
            'available_slots' => $totalAvailability,
            'max_capacity' => $totalAvailability,
            'price' => $price,
            'time_slots' => [],
        ];
    }
}
