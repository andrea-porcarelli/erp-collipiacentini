<div class="slot-variants mt-3 pt-3 border-top">

    <div id="sortable-sv-{{ $slot->id }}">
    @forelse($variants as $vi => $variant)
        <div wire:key="sv-{{ $slot->id }}-{{ $vi }}" data-id="{{ $variant['id'] }}" style="position: relative">

            <div style="position:absolute; top: 52px; left: -25px">
                <span class="drag-handle text-secondary" style="cursor:grab;touch-action:none">
                    <i class="fa-regular fa-bars"></i>
                </span>
            </div>
            {{-- Campi variante --}}
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-4 gap-2">
                    <x-input wire:model="variants.{{ $vi }}.label"
                             name="sv_label_{{ $slot->id }}_{{ $vi }}"
                             label="Nome variante *"
                             placeholder="es. Intero"
                             class="@error('variants.'.$vi.'.label') is-invalid @enderror" />
                    @error('variants.'.$vi.'.label')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm-6">
                    <x-input wire:model="variants.{{ $vi }}.description"
                             name="sv_desc_{{ $slot->id }}_{{ $vi }}"
                             label="Descrizione breve"
                             placeholder="es. Visita guidata completa" />
                </div>
                <div class="col-12 col-sm-2 gap-2 d-flex align-items-end">
                    <x-input wire:model="variants.{{ $vi }}.max_quantity"
                             name="sv_max_{{ $slot->id }}_{{ $vi }}"
                             type="number"
                             label="Massimi consentiti"
                             placeholder="∞"
                             class="@error('variants.'.$vi.'.max_quantity') is-invalid @enderror" />
                    @error('variants.'.$vi.'.max_quantity')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    @if($variant['id'])
                        <x-button
                            status="Neutral"
                            size="Small"
                            leading="fa-language"
                            class="btn-slot-variant-translations"
                            :dataset="['id' => $variant['id']]"
                            style="margin-bottom: 20px"
                        />
                    @endif
                    <button style="margin-bottom: 20px" wire:click="removeVariant({{ $vi }})"
                            wire:confirm="Eliminare la variante '{{ $variant['label'] ?: 'nuova' }}'?"
                            class="bt-miticko outlined danger small">
                        <i class="fa-regular fa-trash-can icon"></i>
                    </button>
                </div>
            </div>

            {{-- Componenti IVA --}}
            <div class="mt-3 mb-2">
                <p class="text-secondary mb-0 small">Componenti IVA</p>
            </div>

            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="small fw-semibold flex-grow-1">Servizio *</span>
                <span class="small fw-semibold" style="width:160px;flex-shrink:0">Prezzo al pubblico *</span>
                <span class="small fw-semibold" style="width:130px;flex-shrink:0">IVA *</span>
                <span style="width:36px;flex-shrink:0"></span>
            </div>

            @foreach($variant['prices'] as $pi => $priceRow)
                <div wire:key="sv-price-{{ $slot->id }}-{{ $vi }}-{{ $pi }}" class="d-flex align-items-center gap-2 mb-2">
                    <div class="flex-grow-1">
                        <x-input wire:model="variants.{{ $vi }}.prices.{{ $pi }}.label"
                                 name="sv_pl_{{ $slot->id }}_{{ $vi }}_{{ $pi }}"
                                 placeholder="es. Visita"
                                 class="@error('variants.'.$vi.'.prices.'.$pi.'.label') is-invalid @enderror" />
                        @error('variants.'.$vi.'.prices.'.$pi.'.label')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-field" data-mode="medium" style="width:160px;flex-shrink:0">
                        <div class="text-field" data-mode="textfieldSize-Medium">
                            <div class="text-field-container">
                                <input  wire:model="variants.{{ $vi }}.prices.{{ $pi }}.price"
                                        class="input-miticko @error('variants.'.$vi.'.prices.'.$pi.'.price') is-invalid @enderror"
                                        name="av_price"
                                        id="av_price"
                                        type="number"
                                        placeholder="0.00"
                                        min="0"
                                        step="0.01"
                                >
                                <i class="extra">EUR</i>
                            </div>
                        </div>
                        @error('variants.'.$vi.'.prices.'.$pi.'.price')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div style="width:130px;flex-shrink:0">
                        <x-select wire:model="variants.{{ $vi }}.prices.{{ $pi }}.vat_rate"
                                  name="sv_vat_{{ $slot->id }}_{{ $vi }}_{{ $pi }}"
                                  class="@error('variants.'.$vi.'.prices.'.$pi.'.vat_rate') is-invalid @enderror"
                                  :options="[['id'=>0,'label'=>'Esente'],['id'=>4,'label'=>'4%'],['id'=>5,'label'=>'5%'],['id'=>10,'label'=>'10%'],['id'=>22,'label'=>'22%']]" />
                        @error('variants.'.$vi.'.prices.'.$pi.'.vat_rate')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div style="width:36px;flex-shrink:0">
                        <button wire:click="removePriceRow({{ $vi }}, {{ $pi }})"
                                class="bt-miticko outlined danger small">
                            <i class="fa-regular fa-trash-can icon"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            <div class="mt-2">
                <x-button w_click="addPriceRow({{ $vi }})"
                          emphasis="Low" status="Primary" size="Small"
                          label="+ Aggiungi componente IVA" />
            </div>

            {{-- Totale + Salva --}}
            <div class="d-flex justify-content-end align-items-center gap-3 pt-3 mt-2">
                @php $total = collect($variant['prices'])->sum(fn($p) => (float) ($p['price'] ?? 0)); @endphp
                <span class="fw-bold slot-variant-price">{{ number_format($total, 0) }}€</span>
                <x-button w_click="save({{ $vi }})"
                          emphasis="High" status="Primary" size="Small"
                          label="Salva modifiche" leading="fa-save" />
            </div>

        </div>
        <hr class="mt-spacing-2xl mb-spacing-2xl" style="color: #E6E6E6" />
    @empty
        <p class="text-secondary small mb-2">Nessuna variante aggiunta.</p>
    @endforelse
    </div>{{-- /sortable-sv --}}

    <div class="mt-spacing-3xl">
        <x-button status="Secondary" emphasis="MediumLow" label="Aggiungi variante" trailing="fa-plus"
                  :dataset="['bs-toggle' => 'modal', 'bs-target' => '#modal-av-'.$slot->id]" />
    </div>

    {{-- Modale: Aggiungi nuova variante --}}
    <div class="modal fade" id="modal-av-{{ $slot->id }}" tabindex="-1" wire:ignore>
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-miticko">
                <div class="modal-header">
                    <h1 class="modal-title">Aggiungi nuova variante</h1>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span class="fa-regular fa-times"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-4">
                            <x-input name="av_label" label="Nome variante *" placeholder="es. Intero" message="Visualizzato dai clienti in fase di selezione" icon="fa-regular fa-circle-info" />
                        </div>
                        <div class="col-12 col-sm-5">
                            <x-input name="av_desc" label="Descrizione breve" placeholder="es. Visita guidata completa" message="Descrizione breve visualizzata in fase di selezione biglietti" icon="fa-regular fa-circle-info" />
                        </div>
                        <div class="col-12 col-sm-3">
                            <x-input name="av_max" type="number" label="Massimi consentiti" placeholder="∞" trailing="fa-user-group" />
                        </div>
                    </div>
                    <div class="mb-1">
                        <hr class="mt-spacing-2xl mb-spacing-2xl" style="color: #E6E6E6" />
                        <p class="fw-semibold mb-1">Componenti IVA</p>
                        <x-supporting-text message="Se il prodotto include servizi con IVA di natura diversa, puoi indicarli qui sotto. I nomi sono per uso interno e non verranno visualizzati dai clienti, tuttavia, potrebbero comparire in fattura se emessa dal sistema."/>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2 px-1 mt-spacing-2xl">
                        <span class="small fw-semibold text-secondary flex-grow-1">Servizio *</span>
                        <span class="small fw-semibold text-secondary" style="width:160px;flex-shrink:0">Prezzo al pubblico *</span>
                        <span class="small fw-semibold text-secondary" style="width:130px;flex-shrink:0">IVA *</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <x-input name="av_price_label" placeholder="es. Visita" />
                        </div>
                        <div style="width:160px;flex-shrink:0">
                            <x-input name="av_price" placeholder="0.00" type="number" extra="EUR"/>
                        </div>
                        <div style="width:130px;flex-shrink:0">
                            <x-select name="av_vat"
                                      :options="[['id'=>0,'label'=>'Esente'],['id'=>4,'label'=>'4%'],['id'=>5,'label'=>'5%'],['id'=>10,'label'=>'10%'],['id'=>22,'label'=>'22%']]"
                                      :value="22" />
                        </div>
                    </div>
                    <x-supporting-text message="Potrai inserire altre componenti IVA dopo aver creato la variante" icon="fa-regular fa-circle-info"/>

                </div>
                <div class="modal-footer">
                    <x-button size="Small" emphasis="Low" status="Primary" label="annulla" :dataset="['bs-dismiss' => 'modal']" />
                    <x-button size="Small" emphasis="High" status="Primary" label="Crea variante" class="btn-av-create" />
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('slot-variants-notify', ({ type, message }) => {
            toastr[type]?.(message);
        });

        let _sortable = null;
        const initSortable = () => {
            const el = document.getElementById('sortable-sv-{{ $slot->id }}');
            if (!el) return;
            if (_sortable) { _sortable.destroy(); _sortable = null; }
            _sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd() {
                    const ids = [...el.querySelectorAll('[data-id]')]
                        .map(el => parseInt(el.dataset.id))
                        .filter(id => id);
                    if (ids.length) $wire.reorder(ids);
                },
            });
        };
        initSortable();
        $wire.on('slot-variants-notify', () => setTimeout(initSortable, 50));

        const _$modal = $('#modal-av-{{ $slot->id }}');

        _$modal.on('show.bs.modal', () => {
            _$modal.find('[name="av_label"]').val('');
            _$modal.find('[name="av_desc"]').val('');
            _$modal.find('[name="av_max"]').val('');
            _$modal.find('[name="av_price_label"]').val('');
            _$modal.find('[name="av_price"]').val('');
            _$modal.find('[name="av_vat"]').val('22');
        });

        _$modal.find('.btn-av-create').on('click', async function () {
            const label      = _$modal.find('[name="av_label"]').val().trim();
            const desc       = _$modal.find('[name="av_desc"]').val().trim();
            const maxQty     = _$modal.find('[name="av_max"]').val().trim();
            const priceLabel = _$modal.find('[name="av_price_label"]').val().trim();
            const price      = _$modal.find('[name="av_price"]').val().trim();
            const vatRate    = parseInt(_$modal.find('[name="av_vat"]').val()) || 0;

            this.disabled = true;
            const ok = await $wire.createVariant(label, desc, maxQty, priceLabel, price, vatRate);
            this.disabled = false;
            if (ok) _$modal.modal('hide');
        });
    </script>
    @endscript

</div>
