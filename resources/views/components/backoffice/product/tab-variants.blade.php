@props(['model'])

<div class="tab-pane fade" id="variants-panel" role="tabpanel" aria-labelledby="variants-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Capienza" sub_title="" class="position-relative">
                <form id="form-variants-occupancy">
                    <div class="row">
                        <div class="col-12">
                            <x-input :model="$model" name="occupancy" label="Capienza massima per fascia oraria" required message="Ogni biglietto venduto influirà sulla capienza indicata, indipendentemente dalla variante" trailing="fa-users"/>
                        </div>
                    </div>
                    <div class="row mt-3 switch-container">
                        <div class="col-12">
                            <x-switch name="occupancy_for_price" label="Gestisci capienza per variante" message="Assegna una capienza specifica a ogni variante. Il sistema aggiorna i posti disponibili in base alle vendite." />
                        </div>
                        <div class="col-12 mt-4">
                            <x-switch name="free_occupancy_rule" label="Gratuiti con capienza illimitata" message="Le varianti con prezzo 0 € restano disponibili senza limiti e non scalano i posti della fascia oraria." />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
            <x-card title="Varianti prodotto" sub_title="Crea le tipologie di biglietto (es. intero, ridotto) e definisci prezzo e capienza. Puoi riordinare la varianti, l’ordine che imposti qui sarà lo stesso che vedranno gli utenti online in fase di prenotazione." class="mt-4 position-relative">
                @php $variants = $model->variants()->whereNull('availability_id')->with('prices')->orderBy('sort_order')->get(); @endphp

                <div id="sortable-variants">
                    @forelse($variants as $variant)
                        <div class="product-variant-item variant-item" data-id="{{ $variant->id }}">
                            {{-- Header --}}
                            <div class="variant-header d-flex align-items-center gap-2">
                                <span class="drag-handle text-secondary me-1" style="cursor:grab;touch-action:none">
                                    <i class="fa-regular fa-bars"></i>
                                </span>
                                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                    <span class="fw-bold variant-label-text">{{ $variant->label }}</span>
                                    <span class="text-secondary">{{ \App\Facades\Utils::price($variant->full_price) }}</span>
                                </div>
                                <div class="d-flex gap-3 flex-shrink-0 align-items-center">
                                    @if($variant->max_quantity)
                                        <x-supporting-text :message="$variant->max_quantity . ' max'" icon="fa-regular fa-users"/>
                                    @endif
                                    <span class="variant-prices-count text-secondary small">
                                        {{ $variant->prices->count() . ($variant->prices->count() == 1 ? ' componente' : ' componenti') . ' IVA' }}
                                    </span>
                                    <button type="button" class="bt-miticko outlined danger small btn-variant-delete">
                                        <i class="fa-regular fa-trash-can icon"></i>
                                    </button>
                                    <button type="button" class="bt-miticko outlined secondary small btn-variant-toggle">
                                        <i class="fa-regular fa-chevron-down icon"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Pannello di modifica (nascosto) --}}
                            <div class="variant-edit-panel mt-spacing">
                                <div class="row g-3  align-items-start">
                                    <div class="col-12 col-sm-4">
                                        <x-input
                                            name="edit_label"
                                             placeholder="es. Intero"
                                             :value="$variant->label"
                                             message="Visualizzato dai clienti in fase di selezione"
                                             icon="fa-regular fa-circle-info"
                                        />
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input
                                            name="edit_description"
                                             placeholder="es. Biglietto intero per adulti"
                                             :value="$variant->description"
                                             message="Descrizione breve visualizzata in fase di selezione"
                                             icon="fa-regular fa-circle-info"
                                        />
                                    </div>
                                    <div class="col-12 col-sm-2 d-flex gap-2">
                                        <x-input
                                            name="edit_max_quantity"
                                            placeholder="es. 25"
                                            :value="$variant->max_quantity"
                                            trailing="fa-users"
                                        />
                                        <button
                                            data-mode="buttonSize-Small buttonEmphasis-Medium  buttonAppearance-Neutral"
                                            type="button"
                                            class="bt-miticko btn-variant-translations btn-miticko-Medium"
                                            style="margin-bottom: 10px; margin-top: 10px"
                                        >
                                            <i class="fa-regular fa-language icon"></i>
                                        </button>
                                    </div>
                                    <hr class="mt-spacing-2xl mb-spacing-2xl" style="color: #E6E6E6" />
                                </div>

                                <div class="mb-3">
                                    <p class="fw-semibold text-main mb-1">Componenti IVA</p>
                                    <p class="text-secondary small mb-0">Se il prodotto include servizi con IVA di natura diversa, puoi indicarle qui sotto. I nomi sono per uso interno e non verranno visualizzati dai clienti, tuttavia, potrebbero comparire in fattura se emessa dal sistema.</p>
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-2 px-1">
                                    <span class="small fw-semibold text-main flex-grow-1">Servizio *</span>
                                    <span class="small fw-semibold text-main" style="width:180px;flex-shrink:0">Prezzo al pubblico *</span>
                                    <span class="small fw-semibold text-main" style="width:150px;flex-shrink:0">IVA *</span>
                                    <span style="width:40px;flex-shrink:0"></span>
                                </div>

                                <div class="variant-edit-prices">
                                    @foreach($variant->prices as $price)
                                        <div class="edit-price-row d-flex align-items-center gap-2 mb-2" data-price-id="{{ $price->id }}">
                                            <div class="flex-grow-1">
                                                <x-input
                                                    name="price_label[]"
                                                    placeholder="es. Visita"
                                                    :value="$price->label"
                                                />
                                            </div>
                                            <div style="width:180px;flex-shrink:0">
                                                <x-input
                                                    name="price_value[]"
                                                    type="number"
                                                    extra="EUR"
                                                    :value="$price->price"
                                                />
                                            </div>
                                            <div style="width:150px;flex-shrink:0">
                                                <div class="text-field" data-mode="textfieldSize-Medium">
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
                                                    <i class="fa-regular fa-trash-can icon"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2">
                                    <x-button class=" "
                                              emphasis="Low"
                                              status="Primary"
                                              size="Small"
                                              label="+ Aggiungi componente IVA" />
                                </div>

                                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                                    <x-button size="Small" emphasis="Low" status="Primary" label="annulla" class="btn-variant-cancel" />
                                    <x-button size="Small" emphasis="High" status="Primary" label="Salva modifiche" class="btn-variant-save" />
                                </div>
                            </div>

                        </div>
                    @empty
                        <p class="text-secondary small mb-4" id="variants-empty">Nessuna variante aggiunta.</p>
                    @endforelse
                </div>

                <div class="button-card-absolute">
                    <x-button status="Primary" label="Aggiungi variante" leading="fa-plus" :dataset="['bs-toggle' => 'modal', 'bs-target' => '#modal-add-variant']" />
                </div>
            </x-card>
            <x-card title="Modifica prezzi in blocco" sub_title="qui puoi definire modifiche di prezzo in blocco in euro o in percentuale del prodotto. Le varianti gratuite non subiranno alcuna variazione." class="mt-4 position-relative">
                <div id="price-variations-list">
                    @forelse($model->priceVariations()->orderBy('date_from')->get() as $variation)
                        <div class="d-flex align-items-center price-variation-item"
                             data-id="{{ $variation->id }}"
                             data-date-from="{{ $variation->date_from->format('Y-m-d') }}"
                             data-date-to="{{ $variation->date_to->format('Y-m-d') }}"
                             data-direction="{{ $variation->direction }}"
                             data-value="{{ $variation->value }}"
                             data-unit="{{ $variation->unit }}">
                            <div class="d-flex gap-3">
                                <b>
                                    {{ $variation->date_from->locale('it')->isoFormat('D MMMM YYYY') }} → {{ $variation->date_to->locale('it')->isoFormat('D MMMM YYYY') }}
                                </b>
                                <span>
                                {{ $variation->direction_label }} {{ number_format($variation->value, 2) }} {{ $variation->unit_label }}
                            </span>
                            </div>
                            <div class="d-flex gap-4">
                                <button type="button" class="bt-miticko outlined danger small btn-edit-price-variation ms-auto">
                                    <i class="fa-regular fa-pen  icon"></i>
                                </button>
                                <button type="button" class="bt-miticko outlined danger small btn-delete-price-variation ms-auto">
                                    <i class="fa-regular fa-trash-can  icon"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0" id="price-variations-empty">Nessuna personalizzazione definita.</p>
                    @endforelse
                </div>
                <div class="button-card-absolute">
                    <x-button status="Primary" label="Crea personalizzazione" trailing="fa-plus" class="btn-add-prices-editing" />
                </div>
            </x-card>
        </div>
    </div>
</div>
{{-- Modal: Aggiungi variante --}}
<div class="modal fade" id="modal-add-variant" tabindex="-1">
    <div class="modal-dialog modal-xl">
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
                <x-supporting-text message="Potrai inserire altre componenti IVA dopo aver creato la variante" icon="fa-regular fa-circle-info"/>
            </div>
            <div class="modal-footer">
                <x-button size="Small" emphasis="Low" status="Primary" label="annulla" :dataset="['bs-dismiss' => 'modal']" />
                <x-button size="Small" emphasis="High" status="Primary" label="Crea variante" id="btn-create-variant" />
            </div>
        </div>
    </div>
</div>

{{-- Modal: Crea personalizzazione prezzo --}}
<div class="modal fade" id="modal-price-variation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Modifica intero periodo</h1>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body">
                <x-supporting-text message="le modifiche verranno applicate all'intero periodo selezionato"/>
                {{-- Periodo --}}
                <p class="fw-semibold mb-2 mt-spacing-2xl">Periodo</p>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span id="pv-date-from-label" class="fw-medium">—</span>
                    <i class="fa-regular fa-arrow-right text-secondary"></i>
                    <span id="pv-date-to-label" class="fw-medium">—</span>
                </div>
                <input type="hidden" id="pv-variation-id" />
                <input type="hidden" id="pv-date-from" />
                <input type="hidden" id="pv-date-to" />

                <x-button size="Medium" emphasis="Medium" status="Secondary" label="seleziona periodo" id="btn-pv-open-picker" leading="fa-calendar" />
                <input type="text" id="pv-flatpickr-input" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0" />

                {{-- Aumento di prezzo --}}
                <p class="fw-semibold mb-3 mt-spacing-2xl">Variazione di prezzo</p>
                <div class="d-flex gap-3">
                    <div style="width:160px">
                        <x-select name="pv_direction" label="Variazione"
                                  :options="[['id'=>'increment','label'=>'Incremento'],['id'=>'decrement','label'=>'Decremento']]"
                                  :value="'increment'" />
                    </div>
                    <div style="width:120px">
                        <x-input name="pv_increment" label="Valore" placeholder="0.00" type="number" />
                    </div>
                    <div class="flex-grow-1">
                        <x-select name="pv_unit" label="Unità di misura"
                                  :options="[['id'=>'euro','label'=>'Euro (€)'],['id'=>'percent','label'=>'Percentuale (%)']]"
                                  :value="'euro'" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <x-button size="Small" emphasis="Low" status="Primary" label="annulla" :dataset="['bs-dismiss' => 'modal']" />
                <x-button size="Small" emphasis="High" status="Primary" label="Crea personalizzazione" id="btn-create-price-variation" />
            </div>
        </div>
    </div>
</div>
