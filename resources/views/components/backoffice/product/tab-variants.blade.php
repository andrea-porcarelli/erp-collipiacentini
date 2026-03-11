@props(['model'])

<div class="tab-pane fade" id="variants-panel" role="tabpanel" aria-labelledby="variants-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Capienza" sub_title="" class="position-relative">
                <form id="form-info-settings">
                    <div class="row">
                        <div class="col-12">
                            <x-input :model="$model" name="occupancy" label="Capienza massima per fascia oraria" required message="Ogni biglietto venduto influirà sulla capienza indicata, indipendentemente dalla variante" trailing="fa-users"/>
                        </div>
                    </div>
                    <div class="row mt-3 switch-container">
                        <div class="col-12">
                            <x-switch name=" " class="occupancy_for_price-switch" label="Gestisci capienza per variante" message="Assegna una capienza specifica a ogni variante. Il sistema aggiorna i posti disponibili in base alle vendite." />
                        </div>
                        <div class="col-12 mt-4">
                            <x-switch name="free_occupancy_rule" label="Gratuiti con capienza illimitata" message="Le varianti con prezzo 0 € restano disponibili senza limiti e non scalano i posti della fascia oraria." />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>
            <x-card title="Varianti prodotto" sub_title="Crea le tipologie di biglietto (es. intero, ridotto) e definisci prezzo e capienza. Puoi riordinare la varianti, l’ordine che imposti qui sarà lo stesso che vedranno gli utenti online in fase di prenotazione." class="mt-4 position-relative">
                @php $variants = $model->variants()->with('prices')->orderBy('sort_order')->get(); @endphp

                <div id="sortable-variants">
                    @forelse($variants as $variant)
                        <div class="variant-item border rounded mb-2" data-id="{{ $variant->id }}">

                            {{-- Header --}}
                            <div class="variant-header d-flex align-items-center gap-2 p-3">
                                <span class="drag-handle text-secondary me-1" style="cursor:grab;touch-action:none">
                                    <i class="fa-regular fa-bars"></i>
                                </span>
                                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                    <span class="fw-semibold variant-label-text">{{ $variant->label }}</span>
                                    <span class="text-secondary">{{ \App\Facades\Utils::price($variant->full_price) }}</span>
                                </div>
                                <div class="d-flex gap-3 flex-shrink-0 align-items-center">
                                    @if($variant->max_quantity)
                                        <x-supporting-text :message="$variant->max_quantity . ' max'" icon="fa-regular fa-users"/>
                                    @endif
                                    <span class="variant-prices-count text-secondary small">
                                        {{ $variant->prices->count() . ($variant->prices->count() == 1 ? ' componente' : ' componenti') . ‘ IVA’ }}
                                    </span>
                                    <button type="button" class="bt-miticko outlined danger small btn-variant-delete">
                                        <i class="fa-regular fa-trash icon"></i>
                                    </button>
                                    <button type="button" class="bt-miticko outlined secondary small btn-variant-toggle">
                                        <i class="fa-regular fa-chevron-down icon"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Pannello di modifica (nascosto) --}}
                            <div class="variant-edit-panel d-none border-top p-4">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-5">
                                        <div class="text-field" data-mode="medium">
                                            <label>Nome variante *</label>
                                            <div class="text-field-container">
                                                <input class="input-miticko" name="edit_label" value="{{ $variant->label }}" placeholder="es. Intero">
                                            </div>
                                            <x-supporting-text message="Visualizzato dai clienti in fase di selezione" icon="fa-regular fa-circle-info"/>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-5">
                                        <div class="text-field" data-mode="medium">
                                            <label>Descrizione breve</label>
                                            <div class="text-field-container">
                                                <input class="input-miticko" name="edit_description" value="{{ $variant->description }}" placeholder="es. Biglietto intero per adulti">
                                            </div>
                                            <x-supporting-text message="Descrizione breve visualizzata in fase di selezione" icon="fa-regular fa-circle-info"/>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-2">
                                        <div class="text-field" data-mode="medium">
                                            <label>Massimi consentiti</label>
                                            <div class="text-field-container">
                                                <input class="input-miticko" name="edit_max_quantity" type="number" value="{{ $variant->max_quantity }}" placeholder="∞">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 mb-3">
                                    <p class="fw-semibold mb-1">Componenti IVA</p>
                                    <p class="text-secondary small mb-0">Se il prodotto include servizi con IVA di natura diversa, puoi indicarle qui sotto. I nomi sono per uso interno e non verranno visualizzati dai clienti, tuttavia, potrebbero comparire in fattura se emessa dal sistema.</p>
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-2 px-1">
                                    <span class="small fw-semibold text-secondary flex-grow-1">Servizio *</span>
                                    <span class="small fw-semibold text-secondary" style="width:180px;flex-shrink:0">Prezzo al pubblico *</span>
                                    <span class="small fw-semibold text-secondary" style="width:150px;flex-shrink:0">IVA *</span>
                                    <span style="width:40px;flex-shrink:0"></span>
                                </div>

                                <div class="variant-edit-prices">
                                    @foreach($variant->prices as $price)
                                        <div class="edit-price-row d-flex align-items-center gap-2 mb-2" data-price-id="{{ $price->id }}">
                                            <div class="flex-grow-1">
                                                <div class="text-field" data-mode="medium">
                                                    <div class="text-field-container">
                                                        <input class="input-miticko" name="price_label[]" value="{{ $price->label }}" placeholder="es. Visita">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-field" data-mode="medium" style="width:180px;flex-shrink:0">
                                                <div class="text-field-container">
                                                    <input type="number" min="0" step="0.01" class="input-miticko" name="price_value[]" value="{{ $price->price }}" placeholder="0.00" style="min-width:0">
                                                    <span class="text-secondary small fw-semibold px-2" style="white-space:nowrap">EUR</span>
                                                </div>
                                            </div>
                                            <div style="width:150px;flex-shrink:0">
                                                <div class="text-field" data-mode="medium">
                                                    <div class="text-field-container">
                                                        <select class="input-miticko" name="price_vat[]">
                                                            <option value="0" {{ (int)$price->vat_rate === 0 ? 'selected' : '' }}>Esente</option>
                                                            <option value="4" {{ (int)$price->vat_rate === 4 ? 'selected' : '' }}>4%</option>
                                                            <option value="5" {{ (int)$price->vat_rate === 5 ? 'selected' : '' }}>5%</option>
                                                            <option value="10" {{ (int)$price->vat_rate === 10 ? 'selected' : '' }}>10%</option>
                                                            <option value="22" {{ (int)$price->vat_rate === 22 ? 'selected' : '' }}>22%</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="width:40px;flex-shrink:0">
                                                <button type="button" class="bt-miticko outlined danger small btn-edit-remove-price">
                                                    <i class="fa-regular fa-trash icon"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2">
                                    <button type="button" class="bt-miticko bt-m-text-only primary small btn-edit-add-price">
                                        <i class="fa-regular fa-plus icon"></i> Aggiungi componente IVA
                                    </button>
                                </div>

                                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                                    <button type="button" class="bt-miticko outlined secondary small btn-variant-cancel">Annulla</button>
                                    <button type="button" class="bt-miticko primary small btn-variant-save">
                                        <i class="fa-regular fa-save icon"></i> Salva modifiche
                                    </button>
                                </div>
                            </div>

                        </div>
                    @empty
                        <p class="text-secondary small mb-4" id="variants-empty">Nessuna variante aggiunta.</p>
                    @endforelse
                </div>

                <div class="button-card-absolute">
                    <button type="button" data-mode="medium primary" class="bt-miticko bt-m-light"
                            data-bs-toggle="modal" data-bs-target="#modal-add-variant">
                        <i class="fa-regular fa-plus icon"></i> Aggiungi variante
                    </button>
                </div>
            </x-card>
        </div>
    </div>
</div>

{{-- Modal: Aggiungi variante --}}
<div class="modal fade" id="modal-add-variant" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Aggiungi variante</h1>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">
                <div class="row g-3">
                    <div class="col-12 col-sm-5">
                        <x-input name="variant_label" label="Nome variante" required
                                 placeholder="es. Intero, Ridotto, Gratuito..."
                                 message="Visualizzato dai clienti in fase di selezione"
                                 icon="fa-regular fa-circle-info" />
                    </div>
                    <div class="col-12 col-sm-5">
                        <x-input name="variant_description" label="Descrizione breve"
                                 placeholder="es. Biglietto intero per adulti"
                                 message="Descrizione breve visualizzata in fase di selezione"
                                 icon="fa-regular fa-circle-info" />
                    </div>
                    <div class="col-12 col-sm-2">
                        <x-input name="variant_max_quantity" type="number" label="Max consentiti" placeholder="∞" />
                    </div>
                    <div class="col-12"><hr class="my-1"/></div>
                    <div class="col-12">
                        <p class="small fw-semibold mb-2">Componenti IVA</p>
                    </div>
                    <div class="col-12" id="variant-prices-list"></div>
                    <div class="col-12 mt-1">
                        <button type="button" id="btn-add-price-row" class="bt-miticko bt-m-text-only secondary small">
                            <i class="fa-regular fa-plus icon"></i> Aggiungi componente IVA
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="bt-miticko bt-m-text-only small" data-bs-dismiss="modal">Annulla</button>
                <button type="button" id="btn-create-variant" class="bt-miticko bt-m-primary small">
                    <i class="fa-regular fa-save icon"></i> Crea variante
                </button>
            </div>
        </div>
    </div>
</div>
