<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, string $id): JsonResponse
    {
        $company = $request->get('company');
        $numericId = $this->extractNumericId($id);

        $product = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->find($numericId);

        if (!$product) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Prodotto non trovato',
            ], 404);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'I parametri start_date e end_date sono obbligatori',
            ], 422);
        }

        $availabilities = ProductAvailability::where('product_id', $product->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $price = $product->prices()->first();
        $priceValue = $price ? (float) $price->price : null;

        $grouped = $availabilities->groupBy('date');
        $availability = [];

        foreach ($grouped as $date => $dateAvailabilities) {
            $hasTimeSlots = $dateAvailabilities->whereNotNull('time')->where('time', '!=', '')->isNotEmpty();

            if ($hasTimeSlots) {
                $timeSlots = $dateAvailabilities
                    ->whereNotNull('time')
                    ->where('time', '!=', '')
                    ->map(fn($avail) => [
                        'start' => $avail->time,
                        'available_slots' => (int) $avail->availability,
                        'max_capacity' => (int) $avail->availability,
                    ])
                    ->values()
                    ->toArray();

                $anyAvailable = $dateAvailabilities->sum('availability') > 0;

                $availability[$date] = [
                    'available' => $anyAvailable,
                    'available_slots' => (int) $dateAvailabilities->sum('availability'),
                    'max_capacity' => (int) $dateAvailabilities->sum('availability'),
                    'price' => $priceValue,
                    'time_slots' => $timeSlots,
                ];
            } else {
                $totalAvailability = (int) $dateAvailabilities->sum('availability');

                $availability[$date] = [
                    'available' => $totalAvailability > 0,
                    'available_slots' => $totalAvailability,
                    'max_capacity' => $totalAvailability,
                    'price' => $priceValue,
                    'time_slots' => [],
                ];
            }
        }

        return response()->json([
            'product_id' => 'ERP-PROD-' . $product->id,
            'availability' => $availability,
        ]);
    }

    public function check(Request $request, string $id): JsonResponse
    {
        $company = $request->get('company');
        $numericId = $this->extractNumericId($id);

        $product = Product::whereHas('partner', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->find($numericId);

        if (!$product) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Prodotto non trovato',
            ], 404);
        }

        $date = $request->input('date');
        $quantity = (int) $request->input('quantity', 1);
        $timeSlot = $request->input('time_slot');

        if (!$date) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'Il parametro date è obbligatorio',
            ], 422);
        }

        $query = ProductAvailability::where('product_id', $product->id)
            ->where('date', $date);

        if ($timeSlot) {
            $query->where('time', $timeSlot);
        }

        $availabilities = $query->get();
        $totalAvailable = (int) $availabilities->sum('availability');
        $isAvailable = $totalAvailable >= $quantity;

        $price = $product->prices()->first();

        return response()->json([
            'available' => $isAvailable,
            'available_slots' => $totalAvailable,
            'max_capacity' => $totalAvailable,
            'price' => $price ? (float) $price->price : null,
            'message' => $isAvailable ? null : 'Disponibilità insufficiente per la quantità richiesta',
        ]);
    }

    private function extractNumericId(string $id): int
    {
        if (str_starts_with($id, 'ERP-PROD-')) {
            return (int) substr($id, 9);
        }

        return (int) $id;
    }
}
