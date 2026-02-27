<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Livewire\Component;

class ProductVariants extends Component
{
    public Product $product;

    // Form nuova variante
    public array $newVariant = ['label' => '', 'description' => '', 'max_quantity' => ''];

    // Righe componenti IVA per la nuova variante
    public array $newVariantPrices = [];

    // Editing variante
    public ?int $editingVariantId = null;
    public array $editVariant = [];

    // Form nuova componente IVA (keyed by variant_id)
    public array $newPrice = [];

    // Editing componente IVA
    public ?int $editingPriceId = null;
    public array $editPrice = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    // ─── Varianti ────────────────────────────────────────────────

    public function addPriceRow(): void
    {
        $this->newVariantPrices[] = ['label' => '', 'price' => '', 'vat_rate' => ''];
    }

    public function removePriceRow(int $index): void
    {
        array_splice($this->newVariantPrices, $index, 1);
        $this->newVariantPrices = array_values($this->newVariantPrices);
    }

    public function addVariant(): void
    {
        $rules = [
            'newVariant.label'        => 'required|string|max:255',
            'newVariant.description'  => 'nullable|string|max:500',
            'newVariant.max_quantity' => 'nullable|integer|min:1',
        ];

        $attributes = [
            'newVariant.label'        => 'nome variante',
            'newVariant.description'  => 'descrizione',
            'newVariant.max_quantity' => 'massimi consentiti',
        ];

        foreach ($this->newVariantPrices as $i => $row) {
            $rules["newVariantPrices.{$i}.label"]    = 'required|string|max:255';
            $rules["newVariantPrices.{$i}.price"]    = 'required|numeric|min:0';
            $rules["newVariantPrices.{$i}.vat_rate"] = 'required|numeric|min:0|max:100';
            $attributes["newVariantPrices.{$i}.label"]    = 'servizio';
            $attributes["newVariantPrices.{$i}.price"]    = 'prezzo';
            $attributes["newVariantPrices.{$i}.vat_rate"] = 'IVA';
        }

        $this->validate($rules, [], $attributes);

        $maxOrder = $this->product->variants()->max('sort_order') ?? 0;

        $variant = $this->product->variants()->create([
            'label'        => $this->newVariant['label'],
            'description'  => $this->newVariant['description'] ?: null,
            'max_quantity' => $this->newVariant['max_quantity'] ?: null,
            'sort_order'   => $maxOrder + 1,
        ]);

        foreach ($this->newVariantPrices as $row) {
            $variant->prices()->create([
                'label'    => $row['label'],
                'price'    => $row['price'],
                'vat_rate' => $row['vat_rate'],
            ]);
        }

        $this->newVariant = ['label' => '', 'description' => '', 'max_quantity' => ''];
        $this->newVariantPrices = [];
        $this->notify('success', 'Variante aggiunta con successo');
    }

    public function startEditVariant(int $id): void
    {
        $variant = ProductVariant::findOrFail($id);
        $this->editingVariantId = $id;
        $this->editVariant = [
            'label'        => $variant->label,
            'description'  => $variant->description ?? '',
            'max_quantity' => $variant->max_quantity ?? '',
        ];
    }

    public function updateVariant(): void
    {
        $this->validate([
            'editVariant.label'        => 'required|string|max:255',
            'editVariant.description'  => 'nullable|string|max:500',
            'editVariant.max_quantity' => 'nullable|integer|min:1',
        ], [], [
            'editVariant.label'        => 'nome variante',
            'editVariant.description'  => 'descrizione',
            'editVariant.max_quantity' => 'massimi consentiti',
        ]);

        ProductVariant::findOrFail($this->editingVariantId)->update([
            'label'        => $this->editVariant['label'],
            'description'  => $this->editVariant['description'] ?: null,
            'max_quantity' => $this->editVariant['max_quantity'] ?: null,
        ]);

        $this->editingVariantId = null;
        $this->editVariant = [];
        $this->notify('success', 'Variante aggiornata');
    }

    public function cancelEditVariant(): void
    {
        $this->editingVariantId = null;
        $this->editVariant = [];
    }

    public function deleteVariant(int $id): void
    {
        ProductVariant::findOrFail($id)->delete();
        $this->notify('success', 'Variante eliminata');
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ProductVariant::where('id', $id)
                ->where('product_id', $this->product->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    // ─── Componenti IVA ──────────────────────────────────────────

    public function addPrice(int $variantId): void
    {
        $this->validate([
            "newPrice.{$variantId}.label"    => 'required|string|max:255',
            "newPrice.{$variantId}.price"    => 'required|numeric|min:0',
            "newPrice.{$variantId}.vat_rate" => 'required|numeric|min:0|max:100',
        ], [], [
            "newPrice.{$variantId}.label"    => 'label',
            "newPrice.{$variantId}.price"    => 'prezzo pubblico',
            "newPrice.{$variantId}.vat_rate" => 'aliquota IVA',
        ]);

        ProductVariant::findOrFail($variantId)->prices()->create([
            'label'    => $this->newPrice[$variantId]['label'],
            'price'    => $this->newPrice[$variantId]['price'],
            'vat_rate' => $this->newPrice[$variantId]['vat_rate'],
        ]);

        $this->newPrice[$variantId] = ['label' => '', 'price' => '', 'vat_rate' => ''];
        $this->notify('success', 'Componente IVA aggiunta');
    }

    public function startEditPrice(int $id): void
    {
        $price = ProductVariantPrice::findOrFail($id);
        $this->editingPriceId = $id;
        $this->editPrice = [
            'label'    => $price->label,
            'price'    => $price->price,
            'vat_rate' => $price->vat_rate,
        ];
    }

    public function updatePrice(): void
    {
        $this->validate([
            'editPrice.label'    => 'required|string|max:255',
            'editPrice.price'    => 'required|numeric|min:0',
            'editPrice.vat_rate' => 'required|numeric|min:0|max:100',
        ], [], [
            'editPrice.label'    => 'label',
            'editPrice.price'    => 'prezzo pubblico',
            'editPrice.vat_rate' => 'aliquota IVA',
        ]);

        ProductVariantPrice::findOrFail($this->editingPriceId)->update([
            'label'    => $this->editPrice['label'],
            'price'    => $this->editPrice['price'],
            'vat_rate' => $this->editPrice['vat_rate'],
        ]);

        $this->editingPriceId = null;
        $this->editPrice = [];
        $this->notify('success', 'Componente IVA aggiornata');
    }

    public function cancelEditPrice(): void
    {
        $this->editingPriceId = null;
        $this->editPrice = [];
    }

    public function deletePrice(int $id): void
    {
        ProductVariantPrice::findOrFail($id)->delete();
        $this->notify('success', 'Componente IVA eliminata');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function notify(string $type, string $message): void
    {
        $this->dispatch('variants-notify', type: $type, message: $message);
    }

    public function render()
    {
        return view('livewire.product-variants', [
            'variants' => $this->product->variants()->with('prices')->orderBy('sort_order')->get(),
        ]);
    }
}
