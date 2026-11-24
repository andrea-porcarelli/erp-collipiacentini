<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private ProductAvailabilityService $service) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $product = Product::find($this->extractNumericId($id));

        if (! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Prodotto non trovato'], 404);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'I parametri start_date e end_date sono obbligatori',
            ], 422);
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'validation_error', 'message' => 'Date non valide'], 422);
        }

        $priceValue = (float) $product->lowest_price ?: null;
        $availability = [];

        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($end)) {
            $days = $this->service->getAvailableDaysForMonth($product, $cursor->year, $cursor->month);
            foreach ($days as $date) {
                if ($date < $start->toDateString() || $date > $end->toDateString()) {
                    continue;
                }
                $slots = $this->service->getSlotsForDate($product, $date);
                $timeSlots = $slots->map(fn ($s) => [
                    'start' => $s['time'],
                    'available_slots' => is_null($s['availability']) ? null : (int) $s['availability'],
                    'slot_type' => $s['slot_type'],
                    'slot_id' => $s['slot_id'],
                ])->values()->all();

                $anyAvailable = $slots->contains(fn ($s) => is_null($s['availability']) || $s['availability'] > 0);
                $totalAvailable = $slots->sum(fn ($s) => is_null($s['availability']) ? PHP_INT_MAX : (int) $s['availability']);

                $availability[$date] = [
                    'available' => $anyAvailable,
                    'available_slots' => $totalAvailable === PHP_INT_MAX ? null : $totalAvailable,
                    'price' => $priceValue,
                    'time_slots' => $timeSlots,
                ];
            }
            $cursor->addMonth();
        }

        return response()->json([
            'product_id' => 'ERP-PROD-'.$product->id,
            'availability' => $availability,
        ]);
    }

    public function check(Request $request, string $id): JsonResponse
    {
        $product = Product::find($this->extractNumericId($id));

        if (! $product) {
            return response()->json(['error' => 'not_found', 'message' => 'Prodotto non trovato'], 404);
        }

        $date = $request->input('date');
        $quantity = max(1, (int) $request->input('quantity', 1));
        $timeSlot = $request->input('time_slot');

        if (! $date) {
            return response()->json(['error' => 'validation_error', 'message' => 'Il parametro date è obbligatorio'], 422);
        }

        $slots = $this->service->getSlotsForDate($product, $date);
        if ($timeSlot) {
            $slots = $slots->filter(fn ($s) => $s['time'] === substr($timeSlot, 0, 5));
        }

        $totalAvailable = $slots->sum(fn ($s) => is_null($s['availability']) ? PHP_INT_MAX : (int) $s['availability']);
        $isAvailable = $totalAvailable === PHP_INT_MAX || $totalAvailable >= $quantity;

        return response()->json([
            'available' => $isAvailable,
            'available_slots' => $totalAvailable === PHP_INT_MAX ? null : $totalAvailable,
            'price' => (float) $product->lowest_price ?: null,
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
