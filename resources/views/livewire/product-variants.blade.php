<div>
    {{-- Lista varianti --}}
    <div id="sortable-variants">
        @forelse($variants as $variant)
            <div class="variant-item border rounded mb-2" wire:key="variant-{{ $variant->id }}" data-id="{{ $variant->id }}">

                {{-- Header variante --}}
                <div class="variant-header d-flex align-items-center gap-2 p-3
                    @if($editingVariantId === $variant->id) border-bottom bg-light @endif">

                    {{-- Drag handle --}}
                    <span class="drag-handle text-secondary me-1" style="cursor:grab;touch-action:none">
                        <i class="fa-regular fa-grip-dots-vertical"></i>
                    </span>

                    @if($editingVariantId === $variant->id)
                        {{-- Form di editing inline --}}
                        <div class="row g-2 align-items-end flex-grow-1">
                            <div class="col-12 col-sm-4">
                                <input wire:model="editVariant.label" type="text"
                                       class="input-miticko @error('editVariant.label') is-invalid @enderror"
                                       placeholder="es. Intero" />
                                @error('editVariant.label') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-4">
                                <input wire:model="editVariant.description" type="text"
                                       class="input-miticko"
                                       placeholder="Descrizione breve" />
                            </div>
                            <div class="col-12 col-sm-2">
                                <input wire:model="editVariant.max_quantity" type="number" min="1"
                                       class="input-miticko @error('editVariant.max_quantity') is-invalid @enderror"
                                       placeholder="Max ∞" />
                                @error('editVariant.max_quantity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-auto d-flex gap-1">
                                <button wire:click="updateVariant" class="btn-miticko primary success small">
                                    <i class="fa-regular fa-check icon"></i>
                                </button>
                                <button wire:click="cancelEditVariant" class="btn-miticko outlined secondary small">
                                    <i class="fa-regular fa-xmark icon"></i>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Display --}}
                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                            <span class="fw-semibold">{{ $variant->label }}</span>
                            <span class="text-secondary">{{ Utils::price($variant->full_price) }}</span>
                        </div>
                        <div class="d-flex gap-3 flex-shrink-0">
                            @if($variant->max_quantity)
                               <x-supporting-text :message="$variant->max_quantity . ' max'" icon="fa-regular fa-users"/>
                            @endif
                            <x-supporting-text :message="$variant->prices->count() . ' ' . Str::plural('componente IVA', $variant->prices->count())"/>
                            <button wire:click="deleteVariant({{ $variant->id }})"
                                    wire:confirm="Eliminare la variante '{{ $variant->label }}' e tutte le sue componenti IVA?"
                                    class="bt-miticko outlined danger small">
                                <i class="fa-regular fa-trash icon"></i>
                            </button>
                            {{-- Toggle collapse --}}
                            <button class="bt-miticko outlined secondary small"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse-variant-{{ $variant->id }}"
                                    aria-expanded="false">
                                <i class="fa-regular fa-chevron-down icon"></i>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Corpo collassabile: componenti IVA --}}
                <div class="collapse" id="collapse-variant-{{ $variant->id }}">
                    <div class="p-3 border-top">
                        <p class="small fw-semibold mb-2 text-secondary">Componenti IVA</p>

                        @if($variant->prices->count())
                            <div class="mb-3">
                                @foreach($variant->prices as $price)
                                    <div wire:key="price-{{ $price->id }}">
                                        @if($editingPriceId === $price->id)
                                            <div class="row g-2 align-items-end py-2 border-bottom">
                                                <div class="col-12 col-sm-4">
                                                    <input wire:model="editPrice.label" type="text"
                                                           class="input-miticko @error('editPrice.label') is-invalid @enderror"
                                                           placeholder="es. Adulto" />
                                                    @error('editPrice.label') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-12 col-sm-3">
                                                    <input wire:model="editPrice.price" type="number" min="0" step="0.01"
                                                           class="input-miticko @error('editPrice.price') is-invalid @enderror"
                                                           placeholder="0.00" />
                                                    @error('editPrice.price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-12 col-sm-3">
                                                    <input wire:model="editPrice.vat_rate" type="number" min="0" max="100" step="0.01"
                                                           class="input-miticko @error('editPrice.vat_rate') is-invalid @enderror"
                                                           placeholder="22" />
                                                    @error('editPrice.vat_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-12 col-sm-2 d-flex gap-1">
                                                    <button wire:click="updatePrice" class="btn-miticko primary success small">
                                                        <i class="fa-regular fa-check icon"></i>
                                                    </button>
                                                    <button wire:click="cancelEditPrice" class="btn-miticko outlined secondary small">
                                                        <i class="fa-regular fa-xmark icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                                                <span class="fw-semibold flex-grow-1">{{ $price->label }}</span>
                                                <span class="text-secondary small">€ {{ number_format($price->price, 2, ',', '.') }}</span>
                                                <span class="badge bg-light text-secondary border small">IVA {{ $price->vat_rate }}%</span>
                                                <div class="d-flex gap-1">
                                                    <button wire:click="startEditPrice({{ $price->id }})" class="btn-miticko outlined secondary small">
                                                        <i class="fa-regular fa-pen icon"></i>
                                                    </button>
                                                    <button wire:click="deletePrice({{ $price->id }})"
                                                            wire:confirm="Eliminare la componente '{{ $price->label }}'?"
                                                            class="btn-miticko outlined danger small">
                                                        <i class="fa-regular fa-trash icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-secondary small mb-3">Nessuna componente IVA aggiunta.</p>
                        @endif

                        {{-- Form nuova componente IVA --}}
                        <div class="row g-2 align-items-end border-top pt-3">
                            <div class="col-12 col-sm-4">
                                <label class="form-label small mb-1">Servizio</label>
                                <input wire:model="newPrice.{{ $variant->id }}.label" type="text"
                                       class="input-miticko @error("newPrice.{$variant->id}.label") is-invalid @enderror"
                                       placeholder="es. Adulto" />
                                @error("newPrice.{$variant->id}.label") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-3">
                                <label class="form-label small mb-1">Prezzo pubblico (€)</label>
                                <input wire:model="newPrice.{{ $variant->id }}.price" type="number" min="0" step="0.01"
                                       class="input-miticko @error("newPrice.{$variant->id}.price") is-invalid @enderror"
                                       placeholder="0.00" />
                                @error("newPrice.{$variant->id}.price") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-3">
                                <label class="form-label small mb-1">IVA (%)</label>
                                <input wire:model="newPrice.{{ $variant->id }}.vat_rate" type="number" min="0" max="100" step="0.01"
                                       class="input-miticko @error("newPrice.{$variant->id}.vat_rate") is-invalid @enderror"
                                       placeholder="22" />
                                @error("newPrice.{$variant->id}.vat_rate") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-2">
                                <button wire:click="addPrice({{ $variant->id }})" class="btn-miticko primary success small w-100">
                                    <i class="fa-regular fa-plus icon"></i> Aggiungi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <p class="text-secondary small mb-4">Nessuna variante aggiunta.</p>
        @endforelse
    </div>

    {{-- Form nuova variante --}}
    <div class="border rounded-4 p-3 mt-4">
        <p class="fw-semibold small mb-3">Aggiungi variante</p>
        <div class="row g-2">
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="medium">
                    <label>Nome variante *</label>
                    <div class="text-field-container">
                        <input wire:model="newVariant.label" type="text"
                               class="input-miticko @error('newVariant.label') is-invalid @enderror"
                               placeholder="es. Intero, Ridotto, Gratuito..." />
                    </div>
                    @error('newVariant.label') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <x-supporting-text message="Visualizzato dai clienti in fase di selezione" icon="fa-regular fa-circle-info"/>
                </div>
            </div>
            <div class="col-12 col-sm-7">
                <div class="text-field" data-mode="medium">
                    <label>Descrizione breve</label>
                    <div class="text-field-container">
                        <input wire:model="newVariant.description" type="text"
                               class="input-miticko"
                               placeholder="es. Biglietto intero per adulti" />
                    </div>
                    <x-supporting-text message="Descrizione breve visualizzata in fase di selezione biglietti" icon="fa-regular fa-circle-info"/>
                </div>
            </div>
            <div class="col-12 col-sm-1">
                <div class="text-field" data-mode="medium">
                    <label>Max consentiti</label>
                    <div class="text-field-container">
                        <input wire:model="newVariant.max_quantity" type="number" min="1"
                               class="input-miticko @error('newVariant.max_quantity') is-invalid @enderror"
                               placeholder="∞" />
                    </div>
                    @error('newVariant.max_quantity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            @if(count($newVariantPrices))
                <div class="col-12"><hr class="my-1"/></div>
                <div class="col-12">
                    <p class="small fw-semibold mb-2">Componenti IVA</p>
                </div>
            @endif

            @foreach($newVariantPrices as $i => $priceRow)
                <div class="col-12" wire:key="new-price-row-{{ $i }}">
                    <div class="d-flex align-items-end gap-2">
                        <div class="flex-grow-1">
                            <div class="text-field" data-mode="medium">
                                <label>Servizio *</label>
                                <div class="text-field-container">
                                    <input wire:model="newVariantPrices.{{ $i }}.label" type="text"
                                           class="input-miticko @error("newVariantPrices.{$i}.label") is-invalid @enderror"
                                           placeholder="es. Adulto" />
                                </div>
                                @error("newVariantPrices.{$i}.label") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div style="width:200px;flex-shrink:0">
                            <div class="text-field" data-mode="medium">
                                <label>Prezzo pubblico (€) *</label>
                                <div class="text-field-container">
                                    <input wire:model="newVariantPrices.{{ $i }}.price" type="number" min="0" step="0.01"
                                           class="input-miticko @error("newVariantPrices.{$i}.price") is-invalid @enderror"
                                           placeholder="0.00" />
                                </div>
                                @error("newVariantPrices.{$i}.price") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div style="width:150px;flex-shrink:0">
                            <div class="text-field" data-mode="medium">
                                <label>IVA (%) *</label>
                                <div class="text-field-container">
                                    <input wire:model="newVariantPrices.{{ $i }}.vat_rate" type="number" min="0" max="100" step="0.01"
                                           class="input-miticko @error("newVariantPrices.{$i}.vat_rate") is-invalid @enderror"
                                           placeholder="22" />
                                </div>
                                @error("newVariantPrices.{$i}.vat_rate") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div style="padding-bottom:2px">
                            <button wire:click="removePriceRow({{ $i }})" class="btn-miticko outlined danger small">
                                <i class="fa-regular fa-xmark icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12 d-flex gap-2 mt-1">
                <x-button w_click="addPriceRow" emphasis="text-only" status="secondary" size="small" label="Aggiungi componente IVA" leading="fa-plus" />
            </div>
            <div class="col-12">
                <x-button w_click="addVariant" class="btn-success" emphasis="primary" size="small" label="Crea variante" leading="fa-save" />
            </div>
        </div>
    </div>

    @script
    <script>
        let _sortable = null;

        const initSortable = () => {
            const el = document.getElementById('sortable-variants');
            if (!el) return;
            if (_sortable) { _sortable.destroy(); _sortable = null; }

            _sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd() {
                    const ids = [...el.querySelectorAll('.variant-item[data-id]')]
                        .map(el => parseInt(el.dataset.id));
                    $wire.reorder(ids);
                },
            });
        };

        initSortable();

        // Reinizializza dopo ogni aggiornamento Livewire
        $wire.on('variants-notify', () => {
            setTimeout(initSortable, 50);
        });
    </script>
    @endscript
</div>
