<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ErpProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = $this->prices->first();
        $content = $this->content();

        // Determine booking mode and time slots from availabilities
        $availabilities = $this->availabilities;
        $hasTimeSlots = $availabilities->whereNotNull('time')->where('time', '!=', '')->isNotEmpty();
        $bookingMode = $hasTimeSlots ? 'timeslots' : 'fullday';

        $timeSlots = [];
        if ($hasTimeSlots) {
            $timeSlots = $availabilities
                ->whereNotNull('time')
                ->where('time', '!=', '')
                ->pluck('time')
                ->unique()
                ->sort()
                ->values()
                ->map(fn($time) => ['start' => $time])
                ->toArray();
        }

        $maxCapacity = $availabilities->max('availability') ?? 0;

        // Images
        $images = [];
        foreach ($this->cover as $media) {
            $images[] = [
                'url' => asset('storage/' . $media->file_path),
                'alt' => $media->description ?? $this->label,
            ];
        }
        foreach ($this->gallery as $media) {
            $images[] = [
                'url' => asset('storage/' . $media->file_path),
                'alt' => $media->description ?? $this->label,
            ];
        }

        // Product type mapping
        $productType = match ($this->product_type) {
            'free', 'guided' => 'bookable',
            default => 'bookable',
        };

        return [
            'id' => 'ERP-PROD-' . $this->id,
            'sku' => $this->product_code,
            'name' => $this->label,
            'slug' => Str::slug($this->label),
            'description' => $content?->description,
            'short_description' => $content?->intro,
            'type' => 'simple',
            'status' => $this->is_active->value === '1' ? 'active' : 'inactive',
            'regular_price' => $price ? (float) $price->price : 0,
            'sale_price' => ($price && $price->reduced > 0) ? (float) $price->reduced : null,
            'categories' => $this->category ? [
                [
                    'name' => $this->category->label,
                    'slug' => Str::slug($this->category->label),
                ],
            ] : [],
            'images' => $images,
            'product_type' => $productType,
            'booking_mode' => $bookingMode,
            'booking_capacity' => (int) $maxCapacity,
            'time_slots' => $timeSlots,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
