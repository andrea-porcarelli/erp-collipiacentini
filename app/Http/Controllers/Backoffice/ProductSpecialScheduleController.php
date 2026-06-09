<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\ProductSpecialSchedule;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductSpecialScheduleController extends Controller
{
    private function authorizeAccess(Product $product): void
    {
        $user = Auth::user();
        if (in_array($user->role, ['god', 'admin'])) {
            return;
        }
        if ($user->role === 'partner' && (int) $product->partner_id !== (int) $user->partner_id) {
            abort(403);
        }
    }

    /**
     * Restituisce gli slot speciali per una data specifica.
     * Se la data non ha override, ritorna gli slot del template settimanale
     * come "preview" — non ancora salvati come ProductSpecialSchedule.
     */
    public function index(Product $product, string $date): JsonResponse
    {
        $this->authorizeAccess($product);

        $overrides = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->where('is_disabled', false)
            ->orderBy('time')
            ->get();

        $hasExceptions = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->where('is_disabled', true)
            ->exists();

        if ($overrides->isNotEmpty()) {
            $html = $overrides->map(fn($s) => view('backoffice.products._special_slot_item', ['slot' => $s])->render())->join('');

            return response()->json([
                'html'           => $html,
                'is_override'    => true,
                'is_preview'     => false,
                'has_exceptions' => $hasExceptions,
            ]);
        }

        // Nessun override: mostra il template settimanale per quel giorno,
        // segnando come "disabilitati" gli slot con un record blacklist (is_disabled=true).
        $dayOfWeek = Carbon::parse($date)->isoWeekday();
        $templateSlots = ProductAvailability::where('product_id', $product->id)
            ->whereNotNull('day_of_week')
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('time')
            ->get();

        $blacklistTimes = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->where('is_disabled', true)
            ->pluck('time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->all();

        $html = $templateSlots->map(fn($s) => view('backoffice.products._special_slot_item', [
            'slot'       => $s,
            'isPreview'  => true,
            'isDisabled' => in_array(substr($s->time, 0, 5), $blacklistTimes, true),
        ])->render())->join('');

        return response()->json([
            'html'           => $html,
            'is_override'    => false,
            'is_preview'     => true,
            'has_exceptions' => $hasExceptions,
        ]);
    }

    /**
     * Disabilita o riabilita uno slot del template settimanale per una data specifica.
     * Crea/elimina un record ProductSpecialSchedule con is_disabled=true (blacklist).
     */
    public function toggleDisable(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($product);

        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);

        $existing = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $data['date'])
            ->where('time', $data['time'])
            ->where('is_disabled', true)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['is_disabled' => false]);
        }

        ProductSpecialSchedule::create([
            'product_id'  => $product->id,
            'date'        => $data['date'],
            'time'        => $data['time'],
            'is_disabled' => true,
        ]);

        return response()->json(['is_disabled' => true]);
    }

    /**
     * Restituisce le varianti del template settimanale per una preview slot.
     */
    public function previewVariants(Product $product, ProductAvailability $availability): JsonResponse
    {
        abort_if((int) $availability->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $variants = ProductVariant::where('availability_id', $availability->id)
            ->orderBy('sort_order')
            ->with('prices')
            ->get();

        $html = $variants->map(fn($v) => view('backoffice.products._special_variant_item', [
            'variant'   => $v,
            'isPreview' => true,
        ])->render())->join('');

        return response()->json(['html' => $html]);
    }

    /**
     * Materializza il template settimanale come override per la data:
     * crea ProductSpecialSchedule + ProductVariant + prezzi clonando dal template.
     * Idempotente: se esistono già override per la data, ritorna lo stato corrente.
     * Risponde con la mappa { time → special_schedule_id, variants[ { template_id → id, prices[…] } ] }
     * così che il frontend possa rimpiazzare i data-* delle preview con gli id reali.
     */
    public function materialize(Product $product, string $date): JsonResponse
    {
        $this->authorizeAccess($product);

        $existing = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->where('is_disabled', false)
            ->with(['variants.prices'])
            ->orderBy('time')
            ->get();

        if ($existing->isNotEmpty()) {
            return response()->json(['slots' => $this->mapExistingSlotsResponse($existing)]);
        }

        $blacklistTimes = ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $date)
            ->where('is_disabled', true)
            ->pluck('time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->all();

        $dayOfWeek = Carbon::parse($date)->isoWeekday();
        $templateSlots = ProductAvailability::where('product_id', $product->id)
            ->whereNotNull('day_of_week')
            ->where('day_of_week', $dayOfWeek)
            ->with(['variants.prices'])
            ->orderBy('time')
            ->get()
            ->reject(fn ($s) => in_array(substr($s->time, 0, 5), $blacklistTimes, true));

        $result = [];

        DB::transaction(function () use ($product, $date, $templateSlots, &$result) {
            // La data passa da "template + blacklist" a "override completo":
            // i record blacklist non sono più necessari.
            ProductSpecialSchedule::where('product_id', $product->id)
                ->where('date', $date)
                ->where('is_disabled', true)
                ->delete();

            foreach ($templateSlots as $tSlot) {
                $newSlot = ProductSpecialSchedule::create([
                    'product_id' => $product->id,
                    'date'       => $date,
                    'time'       => $tSlot->time,
                ]);

                $variantsMap = [];
                foreach ($tSlot->variants->sortBy('sort_order') as $tVariant) {
                    $newVariant = ProductVariant::create([
                        'product_id'          => $product->id,
                        'special_schedule_id' => $newSlot->id,
                        'label'               => $tVariant->label,
                        'description'         => $tVariant->description,
                        'max_quantity'        => $tVariant->max_quantity,
                        'sort_order'          => $tVariant->sort_order,
                    ]);

                    $pricesMap = [];
                    foreach ($tVariant->prices as $price) {
                        $newPrice = $newVariant->prices()->create([
                            'label'    => $price->label,
                            'price'    => $price->price,
                            'vat_rate' => $price->vat_rate,
                        ]);
                        $pricesMap[] = ['template_id' => $price->id, 'id' => $newPrice->id];
                    }

                    $variantsMap[] = [
                        'template_id' => $tVariant->id,
                        'id'          => $newVariant->id,
                        'prices'      => $pricesMap,
                    ];
                }

                $result[] = [
                    'time'     => substr($newSlot->time, 0, 5),
                    'id'       => $newSlot->id,
                    'variants' => $variantsMap,
                ];
            }
        });

        return response()->json(['slots' => $result]);
    }

    /**
     * Genera la struttura di risposta della materialize per slot già esistenti
     * (senza mappature template→id perché lo stato è già reale).
     */
    private function mapExistingSlotsResponse($slots): array
    {
        return $slots->map(fn($s) => [
            'time'     => substr($s->time, 0, 5),
            'id'       => $s->id,
            'variants' => [],
        ])->values()->all();
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
        ]);

        // Se esisteva un record blacklist per lo stesso (date, time), rimuovilo:
        // ora quell'orario diventa un override "vero".
        ProductSpecialSchedule::where('product_id', $product->id)
            ->where('date', $data['date'])
            ->where('time', $data['time'])
            ->where('is_disabled', true)
            ->delete();

        $slot = ProductSpecialSchedule::create([
            'product_id'   => $product->id,
            'date'         => $data['date'],
            'time'         => $data['time'],
        ]);

        return response()->json([
            'html' => view('backoffice.products._special_slot_item', ['slot' => $slot])->render(),
        ]);
    }

    /**
     * Elimina un singolo slot speciale.
     */
    public function destroy(Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $slot->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Aggiorna la capienza di uno slot speciale.
     */
    public function updateAvailability(Request $request, Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
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
        abort_if((int) $slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $variants = ProductVariant::where('special_schedule_id', $slot->id)
            ->orderBy('sort_order')
            ->with('prices')
            ->get();

        $html = $variants->map(fn($v) => $this->renderVariantHtml($v))->join('');

        return response()->json(['html' => $html]);
    }

    /**
     * Crea una nuova variante per uno slot speciale.
     */
    public function storeVariant(Request $request, Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
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

        return response()->json(['html' => $this->renderVariantHtml($variant->load('prices'))]);
    }

    /**
     * Aggiorna una variante di uno slot speciale.
     */
    public function updateVariant(Request $request, Product $product, ProductSpecialSchedule $slot, ProductVariant $variant): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
        abort_if((int) $variant->special_schedule_id !== $slot->id, 403);
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

        return response()->json(['html' => $this->renderVariantHtml($variant->load('prices'))]);
    }

    /**
     * Riordina le varianti di uno slot speciale.
     */
    public function reorderVariants(Request $request, Product $product, ProductSpecialSchedule $slot): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
        $this->authorizeAccess($product);

        $request->validate(['ordered_ids' => 'required|array']);

        foreach ($request->ordered_ids as $index => $variantId) {
            ProductVariant::where('id', $variantId)
                ->where('special_schedule_id', $slot->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Elimina una variante di uno slot speciale.
     */
    public function destroyVariant(Product $product, ProductSpecialSchedule $slot, ProductVariant $variant): JsonResponse
    {
        abort_if((int) $slot->product_id !== $product->id, 403);
        abort_if((int) $variant->special_schedule_id !== $slot->id, 403);
        $this->authorizeAccess($product);

        $variant->prices()->delete();
        $variant->delete();

        return response()->json(['ok' => true]);
    }

    private function renderVariantHtml(ProductVariant $v): string
    {
        return view('backoffice.products._special_variant_item', ['variant' => $v])->render();
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
