<?php

namespace App\Livewire;

use App\Models\ProductAvailability;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Livewire\Component;

class SlotVariants extends Component
{
    public ProductAvailability $slot;

    /** @var array<int, array{id: int|null, label: string, description: string, max_quantity: string, prices: array}> */
    public array $variants = [];

    public function mount(ProductAvailability $slot): void
    {
        $this->slot = $slot;
        $this->loadVariants();
    }

    // ─── Varianti ────────────────────────────────────────────────

    public function createVariant(
        string $label,
        string $description,
        string $maxQuantity,
        string $priceLabel,
        string $price,
        int    $vatRate
    ): bool {
        if (trim($label) === '') {
            $this->notify('error', 'Il nome variante è obbligatorio');
            return false;
        }
        if (trim($priceLabel) === '') {
            $this->notify('error', 'Il nome del servizio è obbligatorio');
            return false;
        }
        if ($price === '' || !is_numeric($price) || (float) $price < 0) {
            $this->notify('error', 'Inserisci un prezzo valido');
            return false;
        }

        $variant = ProductVariant::create([
            'product_id'      => $this->slot->product_id,
            'availability_id' => $this->slot->id,
            'label'           => trim($label),
            'description'     => trim($description) ?: null,
            'max_quantity'    => $maxQuantity !== '' ? (int) $maxQuantity : null,
            'sort_order'      => count($this->variants) + 1,
        ]);

        $newPrice = $variant->prices()->create([
            'label'    => trim($priceLabel),
            'price'    => (float) $price,
            'vat_rate' => $vatRate,
        ]);

        $this->variants[] = [
            'id'           => $variant->id,
            'label'        => $variant->label,
            'description'  => $variant->description ?? '',
            'max_quantity' => $variant->max_quantity ?? '',
            'prices'       => [[
                'id'       => $newPrice->id,
                'label'    => $newPrice->label,
                'price'    => $newPrice->price,
                'vat_rate' => (int) $newPrice->vat_rate,
            ]],
        ];

        $this->notify('success', 'Variante creata');
        return true;
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ProductVariant::where('id', $id)
                ->where('availability_id', $this->slot->id)
                ->update(['sort_order' => $index + 1]);
        }

        $indexed = collect($this->variants)->keyBy('id');
        $this->variants = collect($orderedIds)
            ->map(fn($id) => $indexed[$id] ?? null)
            ->filter()
            ->values()
            ->toArray();
    }

    public function removeVariant(int $vi): void
    {
        $id = $this->variants[$vi]['id'] ?? null;
        if ($id) {
            ProductVariant::findOrFail($id)->delete();
        }
        array_splice($this->variants, $vi, 1);
        $this->variants = array_values($this->variants);
        $this->notify('success', 'Variante eliminata');
    }

    public function save(int $vi): void
    {
        $rules      = [
            "variants.{$vi}.label"        => 'required|string|max:255',
            "variants.{$vi}.description"  => 'nullable|string|max:500',
            "variants.{$vi}.max_quantity" => 'nullable|integer|min:1',
        ];
        $attributes = [
            "variants.{$vi}.label"        => 'nome variante',
            "variants.{$vi}.description"  => 'descrizione',
            "variants.{$vi}.max_quantity" => 'massimi consentiti',
        ];

        foreach ($this->variants[$vi]['prices'] as $pi => $row) {
            $rules["variants.{$vi}.prices.{$pi}.label"]    = 'required|string|max:255';
            $rules["variants.{$vi}.prices.{$pi}.price"]    = 'required|numeric|min:0';
            $rules["variants.{$vi}.prices.{$pi}.vat_rate"] = 'required|numeric|min:0|max:100';
            $attributes["variants.{$vi}.prices.{$pi}.label"]    = 'servizio';
            $attributes["variants.{$vi}.prices.{$pi}.price"]    = 'prezzo';
            $attributes["variants.{$vi}.prices.{$pi}.vat_rate"] = 'IVA';
        }

        $this->validate($rules, [], $attributes);

        $data = $this->variants[$vi];
        $id   = $data['id'] ?? null;

        if ($id) {
            $variant = ProductVariant::findOrFail($id);
            $variant->update([
                'label'        => $data['label'],
                'description'  => $data['description'] ?: null,
                'max_quantity' => $data['max_quantity'] ?: null,
            ]);
        } else {
            $variant = ProductVariant::create([
                'product_id'      => $this->slot->product_id,
                'availability_id' => $this->slot->id,
                'label'           => $data['label'],
                'description'     => $data['description'] ?: null,
                'max_quantity'    => $data['max_quantity'] ?: null,
                'sort_order'      => count($this->variants),
            ]);
            $this->variants[$vi]['id'] = $variant->id;
        }

        // Sync prezzi: elimina rimossi, aggiorna/crea presenti
        $keptIds = collect($data['prices'])->pluck('id')->filter()->values();
        $variant->prices()->whereNotIn('id', $keptIds)->delete();

        foreach ($data['prices'] as $pi => $row) {
            if (!empty($row['id'])) {
                ProductVariantPrice::where('id', $row['id'])->update([
                    'label'    => $row['label'],
                    'price'    => $row['price'],
                    'vat_rate' => $row['vat_rate'],
                ]);
            } else {
                $newPrice = $variant->prices()->create([
                    'label'    => $row['label'],
                    'price'    => $row['price'],
                    'vat_rate' => $row['vat_rate'],
                ]);
                $this->variants[$vi]['prices'][$pi]['id'] = $newPrice->id;
            }
        }

        $this->notify('success', 'Variante salvata');
    }

    // ─── Componenti IVA ──────────────────────────────────────────

    public function addPriceRow(int $vi): void
    {
        $this->variants[$vi]['prices'][] = ['id' => null, 'label' => '', 'price' => '', 'vat_rate' => 22];
    }

    public function removePriceRow(int $vi, int $pi): void
    {
        array_splice($this->variants[$vi]['prices'], $pi, 1);
        $this->variants[$vi]['prices'] = array_values($this->variants[$vi]['prices']);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function loadVariants(): void
    {
        $this->variants = ProductVariant::with('prices')
            ->where('availability_id', $this->slot->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($v) => [
                'id'           => $v->id,
                'label'        => $v->label,
                'description'  => $v->description ?? '',
                'max_quantity' => $v->max_quantity ?? '',
                'prices'       => $v->prices->map(fn($p) => [
                    'id'       => $p->id,
                    'label'    => $p->label,
                    'price'    => $p->price,
                    'vat_rate' => (int) $p->vat_rate,
                ])->toArray(),
            ])
            ->toArray();
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('slot-variants-notify', type: $type, message: $message);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.slot-variants');
    }
}
