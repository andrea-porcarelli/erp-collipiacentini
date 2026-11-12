<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\VatRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $this->ensureGod();

        $vatRates = VatRate::orderBy('sort_order')->get();
        $inUseIds = \App\Models\ProductVariantPrice::whereNotNull('vat_rate_id')
            ->distinct()
            ->pluck('vat_rate_id')
            ->all();
        $miticko = config('fatturapa.miticko');

        return view('backoffice.settings.index', [
            'vatRates' => $vatRates,
            'inUseIds' => $inUseIds,
            'miticko' => $miticko,
        ]);
    }

    public function storeVatRate(Request $request): JsonResponse
    {
        $this->ensureGod();

        $data = $this->validateVatRate($request);

        $vatRate = VatRate::create($data + [
            'sort_order' => (int) (VatRate::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['ok' => true, 'id' => $vatRate->id]);
    }

    public function updateVatRate(Request $request, VatRate $vatRate): JsonResponse
    {
        $this->ensureGod();

        $data = $this->validateVatRate($request, $vatRate->id);
        $vatRate->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroyVatRate(VatRate $vatRate): JsonResponse
    {
        $this->ensureGod();

        // Blocca la cancellazione se referenziata da varianti prezzo.
        $inUse = $vatRate->productVariantPrices()->exists();
        if ($inUse) {
            return response()->json([
                'ok' => false,
                'message' => 'Aliquota in uso da una o più varianti prezzo: non può essere eliminata. Disattivala invece.',
            ], 422);
        }

        $vatRate->delete();

        return response()->json(['ok' => true]);
    }

    public function reorderVatRates(Request $request): JsonResponse
    {
        $this->ensureGod();

        $ids = $request->input('ids', []);
        foreach ($ids as $index => $id) {
            VatRate::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    private function validateVatRate(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:vat_rates,code';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId;
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:20', $uniqueRule],
            'label' => ['required', 'string', 'max:255'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nature' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function ensureGod(): void
    {
        if (Auth::user()?->role !== 'god') {
            abort(403, 'Operazione riservata agli operatori Miticko.');
        }
    }
}
