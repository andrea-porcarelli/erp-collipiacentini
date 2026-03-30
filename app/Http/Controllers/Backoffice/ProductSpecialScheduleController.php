<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpecialSchedule;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSpecialScheduleController extends Controller
{
    private function authorizeAccess(Product $product): void
    {
        $user = Auth::user();
        if (in_array($user->role, ['god', 'admin'])) {
            return;
        }
        if ($user->role === 'partner' && $product->partner_id !== $user->partner_id) {
            abort(403);
        }
    }

    /**
     * Restituisce gli slot speciali per una data specifica.
     */
    public function index(Product $product, string $date): JsonResponse
    {
        $this->authorizeAccess($product);

        $slots = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->orderBy('time')
            ->get();

        return response()->json([
            'slots' => $slots->map(fn($s) => [
                'id'           => $s->id,
                'time'         => substr($s->time, 0, 5),
                'availability' => $s->availability,
            ]),
            'is_override' => $slots->isNotEmpty(),
        ]);
    }

    /**
     * Restituisce tutte le date che hanno slot speciali (per evidenziare il calendario).
     */
    public function dates(Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $dates = ProductSpecialSchedule::where('product_id', $product->id)
            ->distinct()
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'));

        return response()->json(['dates' => $dates]);
    }

    /**
     * Aggiunge un nuovo slot speciale per una data.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $data = $request->validate([
            'date'         => 'required|date_format:Y-m-d',
            'time'         => 'required|date_format:H:i',
            'availability' => 'required|integer|min:0',
        ]);

        $slot = ProductSpecialSchedule::create([
            'product_id'   => $product->id,
            'date'         => $data['date'],
            'time'         => $data['time'],
            'availability' => $data['availability'],
        ]);

        return response()->json([
            'id'           => $slot->id,
            'time'         => substr($slot->time, 0, 5),
            'availability' => $slot->availability,
        ]);
    }

    /**
     * Elimina un singolo slot speciale.
     */
    public function destroy(Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $slot->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Aggiorna la capienza di uno slot speciale.
     */
    public function updateAvailability(Request $request, Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $data = $request->validate([
            'availability' => 'required|integer|min:0',
        ]);

        $slot->update(['availability' => $data['availability']]);

        return response()->json([
            'id'           => $slot->id,
            'availability' => $slot->availability,
        ]);
    }

    /**
     * Restituisce le varianti di uno slot speciale.
     */
    public function getVariants(Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $variants = ProductVariant::where('special_schedule_id', $slot->id)
            ->orderBy('sort_order')
            ->with('prices')
            ->get()
            ->map(fn($v) => $this->serializeVariant($v));

        return response()->json(['variants' => $variants]);
    }

    /**
     * Crea una nuova variante per uno slot speciale.
     */
    public function storeVariant(Request $request, Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $data = $request->validate([
            'label'             => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'max_quantity'      => 'nullable|integer|min:1',
            'prices'            => 'required|array|min:1',
            'prices.*.label'    => 'required|string|max:255',
            'prices.*.price'    => 'required|numeric|min:0',
            'prices.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        $maxOrder = ProductVariant::where('special_schedule_id', $slot->id)->max('sort_order') ?? 0;

        $variant = ProductVariant::create([
            'product_id'          => $product->id,
            'special_schedule_id' => $slot->id,
            'label'               => $data['label'],
            'description'         => $data['description'] ?? null,
            'max_quantity'        => $data['max_quantity'] ?? null,
            'sort_order'          => $maxOrder + 1,
        ]);

        foreach ($data['prices'] as $row) {
            $variant->prices()->create([
                'label'    => $row['label'],
                'price'    => $row['price'],
                'vat_rate' => $row['vat_rate'],
            ]);
        }

        return response()->json(['variant' => $this->serializeVariant($variant->load('prices'))]);
    }

    /**
     * Aggiorna una variante di uno slot speciale.
     */
    public function updateVariant(Request $request, Product $product, ProductSpecialSchedule $slot, ProductVariant $variant): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        abort_if($variant->special_schedule_id !== $slot->id, 403);
        $this->authorizeAccess($product);

        $data = $request->validate([
            'label'             => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'max_quantity'      => 'nullable|integer|min:1',
            'prices'            => 'required|array|min:1',
            'prices.*.id'       => 'nullable|integer',
            'prices.*.label'    => 'required|string|max:255',
            'prices.*.price'    => 'required|numeric|min:0',
            'prices.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        $variant->update([
            'label'        => $data['label'],
            'description'  => $data['description'] ?? null,
            'max_quantity' => $data['max_quantity'] ?? null,
        ]);

        $keptIds = collect($data['prices'])->pluck('id')->filter()->values();
        $variant->prices()->whereNotIn('id', $keptIds)->delete();

        foreach ($data['prices'] as $row) {
            if (!empty($row['id'])) {
                ProductVariantPrice::where('id', $row['id'])->update([
                    'label'    => $row['label'],
                    'price'    => $row['price'],
                    'vat_rate' => $row['vat_rate'],
                ]);
            } else {
                $variant->prices()->create([
                    'label'    => $row['label'],
                    'price'    => $row['price'],
                    'vat_rate' => $row['vat_rate'],
                ]);
            }
        }

        return response()->json(['variant' => $this->serializeVariant($variant->load('prices'))]);
    }

    /**
     * Elimina una variante di uno slot speciale.
     */
    public function destroyVariant(Product $product, ProductSpecialSchedule $slot, ProductVariant $variant): JsonResponse
    {
        abort_if($slot->product_id !== $product->id, 403);
        abort_if($variant->special_schedule_id !== $slot->id, 403);
        $this->authorizeAccess($product);

        $variant->prices()->delete();
        $variant->delete();

        return response()->json(['ok' => true]);
    }

    private function serializeVariant(ProductVariant $v): array
    {
        return [
            'id'           => $v->id,
            'label'        => $v->label,
            'description'  => $v->description,
            'max_quantity' => $v->max_quantity,
            'full_price'   => $v->full_price,
            'prices'       => $v->prices->map(fn($p) => [
                'id'       => $p->id,
                'label'    => $p->label,
                'price'    => $p->price,
                'vat_rate' => $p->vat_rate,
            ])->values(),
        ];
    }

    /**
     * Elimina tutti gli slot speciali di una data (ripristina il template settimanale).
     */
    public function reset(Product $product, string $date): JsonResponse
    {
        $this->authorizeAccess($product);

        ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
