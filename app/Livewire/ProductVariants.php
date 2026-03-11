<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductVariants extends Component
{
    public Product $product;

    // Editing variante
    public ?int $editingVariantId = null;
    public array $editVariant = [];
    public array $editVariantPrices = [];

    // Form nuova componente IVA (keyed by variant_id)
    public array $newPrice = [];

    // Editing componente IVA
    public ?int $editingPriceId = null;
    public array $editPrice = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    #[On('variant-created')]
    public function onVariantCreated(): void
    {
        $this->notify('success', 'Variante aggiunta con successo');
    }

    // ─── Varianti ────────────────────────────────────────────────

    public function startEditVariant(int $id): void
    {
        $variant = ProductVariant::with('prices')->findOrFail($id);
        $this->editingVariantId = $id;
        $this->editVariant = [
            'label'        => $variant->label,
            'description'  => $variant->description ?? '',
            'max_quantity' => $variant->max_quantity ?? '',
        ];
        $this->editVariantPrices = $variant->prices->map(fn($p) => [
            'id'       => $p->id,
            'label'    => $p->label,
            'price'    => $p->price,
            'vat_rate' => (int) $p->vat_rate,
        ])->toArray();
    }

    public function updateVariant(): void
    {
        $rules = [
            'editVariant.label'        => 'required|string|max:255',
            'editVariant.description'  => 'nullable|string|max:500',
            'editVariant.max_quantity' => 'nullable|integer|min:1',
        ];
        $attributes = [
            'editVariant.label'        => 'nome variante',
            'editVariant.description'  => 'descrizione',
            'editVariant.max_quantity' => 'massimi consentiti',
        ];

        foreach ($this->editVariantPrices as $i => $row) {
            $rules["editVariantPrices.{$i}.label"]    = 'required|string|max:255';
            $rules["editVariantPrices.{$i}.price"]    = 'required|numeric|min:0';
            $rules["editVariantPrices.{$i}.vat_rate"] = 'required|numeric|min:0|max:100';
            $attributes["editVariantPrices.{$i}.label"]    = 'servizio';
            $attributes["editVariantPrices.{$i}.price"]    = 'prezzo';
            $attributes["editVariantPrices.{$i}.vat_rate"] = 'IVA';
        }

        $this->validate($rules, [], $attributes);

        $variant = ProductVariant::findOrFail($this->editingVariantId);
        $variant->update([
            'label'        => $this->editVariant['label'],
            'description'  => $this->editVariant['description'] ?: null,
            'max_quantity' => $this->editVariant['max_quantity'] ?: null,
        ]);

        // Sync prices: delete removed, update existing, create new
        $keptIds = collect($this->editVariantPrices)->pluck('id')->filter()->values();
        $variant->prices()->whereNotIn('id', $keptIds)->delete();

        foreach ($this->editVariantPrices as $row) {
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

        $this->editingVariantId = null;
        $this->editVariant = [];
        $this->editVariantPrices = [];
        $this->notify('success', 'Variante aggiornata');
    }

    public function cancelEditVariant(): void
    {
        $this->editingVariantId = null;
        $this->editVariant = [];
        $this->editVariantPrices = [];
    }

    public function addEditPriceRow(): void
    {
        $this->editVariantPrices[] = ['id' => null, 'label' => '', 'price' => '', 'vat_rate' => 22];
    }

    public function removeEditPriceRow(int $index): void
    {
        array_splice($this->editVariantPrices, $index, 1);
        $this->editVariantPrices = array_values($this->editVariantPrices);
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
