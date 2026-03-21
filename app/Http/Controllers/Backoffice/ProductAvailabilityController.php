<?php

namespace App\Http\Controllers\Backoffice;

use App\Models\Product;
use App\Models\ProductAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductAvailabilityController extends CrudController
{
    /**
     * Ritorna tutti gli slot del template settimanale del prodotto,
     * raggruppati per giorno della settimana (1=Lun … 7=Dom).
     */
    public function index(int $productId, int $dayIndex): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $slots = ProductAvailability::where('product_id', $product->id)
                ->where('day_of_week', $dayIndex)
                ->whereNotNull('day_of_week')
                ->orderBy('day_of_week')
                ->orderBy('time')
                ->get(['id', 'day_of_week', 'time']);
            return $this->success([
                'response' => view('backoffice.products.slots', compact('product', 'slots'))->render()
            ]);
        } catch (\Exception $e) {
            dd($e);
            return $this->exception($e);
        }
    }

    /**
     * Aggiunge uno slot orario al template settimanale.
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'day_of_week' => 'required|integer|min:1|max:7',
                'time'        => 'required|date_format:H:i',
            ]);

            $product = Product::findOrFail($productId);

            $slot = ProductAvailability::create([
                'product_id'  => $product->id,
                'day_of_week' => $request->integer('day_of_week'),
                'time'        => $request->input('time'),
            ]);

            return $this->success([
                'id'          => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'time'        => $slot->time,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    /**
     * Aggiorna l'orario di uno slot.
     */
    public function update(Request $request, int $productId, int $slotId): JsonResponse
    {
        try {
            $request->validate([
                'time' => 'required|date_format:H:i',
            ]);

            $slot = ProductAvailability::where('product_id', $productId)
                ->whereNotNull('day_of_week')
                ->findOrFail($slotId);

            $slot->update(['time' => $request->input('time')]);

            return $this->success([
                'id'          => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'time'        => $slot->time,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    /**
     * Elimina uno slot.
     */
    public function destroy(int $productId, int $slotId): JsonResponse
    {
        try {
            ProductAvailability::where('product_id', $productId)
                ->whereNotNull('day_of_week')
                ->findOrFail($slotId)
                ->delete();

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
