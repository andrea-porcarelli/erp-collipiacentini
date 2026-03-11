<div>
    {{-- Lista varianti --}}
    <div id="sortable-variants">
        @forelse($variants as $variant)
            <div class="variant-item border rounded mb-2" wire:key="variant-{{ $variant->id }}" data-id="{{ $variant->id }}">

                {{-- Header variante --}}
                <div class="variant-header d-flex align-items-center gap-2 p-3 @if($editingVariantId === $variant->id) border-bottom @endif">
                    <span class="drag-handle text-secondary me-1" style="cursor:grab;touch-action:none">
                        <i class="fa-regular fa-bars"></i>
                    </span>
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <span class="fw-semibold">{{ $variant->label }}</span>
                        <span class="text-secondary">{{ Utils::price($variant->full_price) }}</span>
                    </div>
                    <div class="d-flex gap-3 flex-shrink-0 align-items-center">
                        @if($variant->max_quantity)
                            <x-supporting-text :message="$variant->max_quantity . ' max'" icon="fa-regular fa-users"/>
                        @endif
                        <x-supporting-text :message="$variant->prices->count() . ($variant->prices->count() == 1 ? ' componente' : ' componenti') .' IVA'"/>
                        @if($editingVariantId !== $variant->id)
                            <button wire:click="deleteVariant({{ $variant->id }})"
                                    wire:confirm="Eliminare la variante '{{ $variant->label }}' e tutte le sue componenti IVA?"
                                    class="bt-miticko outlined danger small">
                                <i class="fa-regular fa-trash icon"></i>
                            </button>
                        @endif
                        @if($editingVariantId === $variant->id)
                            <button wire:click="cancelEditVariant" class="bt-miticko outlined secondary small">
                                <i class="fa-regular fa-chevron-up icon"></i>
                            </button>
                        @else
                            <button wire:click="startEditVariant({{ $variant->id }})" class="bt-miticko outlined secondary small">
                                <i class="fa-regular fa-chevron-down icon"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Pannello di modifica espanso --}}
                @if($editingVariantId === $variant->id)
                    <div class="p-4">
                        {{-- Campi variante --}}
                        <div class="row g-3">
                            <div class="col-12 col-sm-5">
                                <x-input wire:model="editVariant.label"
                                         name="edit_variant_label"
                                         label="Nome variante" required
                                         placeholder="es. Intero"
                                         class="@error('editVariant.label') is-invalid @enderror"
                                         message="Visualizzato dai clienti in fase di selezione"
                                         icon="fa-regular fa-circle-info" />
                                @error('editVariant.label') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-5">
                                <x-input wire:model="editVariant.description"
                                         name="edit_variant_description"
                                         label="Descrizione breve"
                                         placeholder="es. Biglietto intero per adulti"
                                         message="Descrizione breve visualizzata in fase di selezione biglietti"
                                         icon="fa-regular fa-circle-info" />
                            </div>
                            <div class="col-12 col-sm-2">
                                <x-input wire:model="editVariant.max_quantity"
                                         name="edit_variant_max_quantity"
                                         type="number"
                                         label="Massimi consentiti"
                                         placeholder="∞"
                                         class="@error('editVariant.max_quantity') is-invalid @enderror" />
                                @error('editVariant.max_quantity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Sezione componenti IVA --}}
                        <div class="mt-4 mb-3">
                            <p class="fw-semibold mb-1">Componenti IVA</p>
                            <p class="text-secondary small mb-0">Se il prodotto include servizi con IVA di natura diversa, puoi indicarle qui sotto. I nomi sono per uso interno e non verranno visualizzati dai clienti, tuttavia, potrebbero comparire in fattura se emessa dal sistema.</p>
                        </div>

                        {{-- Intestazioni colonne --}}
                        <div class="d-flex align-items-center gap-2 mb-2 px-1">
                            <span class="small fw-semibold text-secondary flex-grow-1">Servizio *</span>
                            <span class="small fw-semibold text-secondary" style="width:180px;flex-shrink:0">Prezzo al pubblico *</span>
                            <span class="small fw-semibold text-secondary" style="width:150px;flex-shrink:0">IVA *</span>
                            <span style="width:40px;flex-shrink:0"></span>
                        </div>

                        {{-- Righe componenti IVA --}}
                        @foreach($editVariantPrices as $i => $priceRow)
                            <div wire:key="edit-price-row-{{ $i }}" class="d-flex align-items-center gap-2 mb-2">
                                {{-- Servizio --}}
                                <div class="flex-grow-1">
                                    <x-input wire:model="editVariantPrices.{{ $i }}.label"
                                             name="edit_price_{{ $i }}_label"
                                             placeholder="es. Visita"
                                             class="@error("editVariantPrices.{$i}.label") is-invalid @enderror" />
                                    @error("editVariantPrices.{$i}.label") <div class="invalid-feedback d-block small">{{ $message }}</div> @enderror
                                </div>
                                {{-- Prezzo (EUR inline, manuale) --}}
                                <div class="text-field" data-mode="medium" style="width:180px;flex-shrink:0">
                                    <div class="text-field-container">
                                        <input wire:model="editVariantPrices.{{ $i }}.price"
                                               type="number" min="0" step="0.01"
                                               class="input-miticko @error("editVariantPrices.{$i}.price") is-invalid @enderror"
                                               placeholder="0.00" style="min-width:0" />
                                        <span class="text-secondary small fw-semibold px-2" style="white-space:nowrap">EUR</span>
                                    </div>
                                    @error("editVariantPrices.{$i}.price") <div class="invalid-feedback d-block small">{{ $message }}</div> @enderror
                                </div>
                                {{-- IVA --}}
                                <div style="width:150px;flex-shrink:0">
                                    <x-select wire:model="editVariantPrices.{{ $i }}.vat_rate"
                                              name="edit_price_{{ $i }}_vat_rate"
                                              class="@error("editVariantPrices.{$i}.vat_rate") is-invalid @enderror"
                                              :options="[['id'=>0,'label'=>'Esente'],['id'=>4,'label'=>'4%'],['id'=>5,'label'=>'5%'],['id'=>10,'label'=>'10%'],['id'=>22,'label'=>'22%']]" />
                                    @error("editVariantPrices.{$i}.vat_rate") <div class="invalid-feedback d-block small">{{ $message }}</div> @enderror
                                </div>
                                {{-- Azioni --}}
                                <div style="width:40px;flex-shrink:0">
                                    <button wire:click="removeEditPriceRow({{ $i }})" class="bt-miticko outlined danger small">
                                        <i class="fa-regular fa-trash icon"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        {{-- Aggiungi componente IVA --}}
                        <div class="mt-2">
                            <x-button w_click="addEditPriceRow" emphasis="text-only" status="primary" size="small" label="+ Aggiungi componente IVA" />
                        </div>

                        {{-- Footer azioni --}}
                        <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                            <x-button w_click="cancelEditVariant" emphasis="outlined" status="secondary" size="small" label="Annulla" />
                            <x-button w_click="updateVariant" emphasis="primary" size="small" label="Salva modifiche" leading="fa-save" />
                        </div>
                    </div>
                @endif

            </div>
        @empty
            <p class="text-secondary small mb-4">Nessuna variante aggiunta.</p>
        @endforelse
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
