@php
    $vatOptions = [0 => 'Esente', 4 => '4%', 5 => '5%', 10 => '10%', 22 => '22%'];
    $fullPrice  = $variant->prices->sum('price');
    $priceCount = $variant->prices->count();
    $compLabel  = $priceCount . ($priceCount === 1 ? ' componente' : ' componenti') . ' IVA';
@endphp

<div class="ss-variant-item" data-variant-id="{{ $variant->id }}">
    <div class="ss-variant-header">
        <span class="drag-handle text-secondary me-2" style="cursor:grab;font-size:18px;line-height:1">⠿</span>
        <span class="fw-bold flex-grow-1">{{ $variant->label }}</span>
        @if($fullPrice)
            <span class="text-secondary small">€ {{ number_format($fullPrice, 2) }}</span>
        @endif
        @if($variant->max_quantity)
            <span class="text-secondary small"><i class="fa-regular fa-users me-1"></i>{{ $variant->max_quantity }} max</span>
        @endif
        <span class="text-secondary small">{{ $compLabel }}</span>
        <button type="button" class="bt-miticko outlined danger small btn-ssv-delete"><i class="fa-regular fa-trash-can icon"></i></button>
        <button type="button" class="bt-miticko outlined secondary small btn-ssv-toggle"><i class="fa-regular fa-chevron-down icon"></i></button>
    </div>
    <div class="ss-variant-edit-panel">
        <div class="row g-3 align-items-start">
            <div class="col-12 col-sm-4">
                <label class="small fw-medium mb-1 d-block">Nome variante</label>
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">
                    <input type="text" class="input-miticko ssv-edit-label" value="{{ $variant->label }}" placeholder="es. Intero">
                </div></div>
            </div>
            <div class="col-12 col-sm-6">
                <label class="small fw-medium mb-1 d-block">Descrizione</label>
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">
                    <input type="text" class="input-miticko ssv-edit-description" value="{{ $variant->description ?? '' }}" placeholder="es. Biglietto intero per adulti">
                </div></div>
            </div>
            <div class="col-12 col-sm-2">
                <label class="small fw-medium mb-1 d-block">Max consentiti</label>
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">
                    <input type="number" class="input-miticko ssv-edit-max" value="{{ $variant->max_quantity ?? '' }}" placeholder="∞" min="1">
                </div></div>
            </div>
        </div>
        <hr class="my-3" style="color:#E6E6E6">
        <p class="fw-semibold small mb-2">Componenti IVA</p>
        <div class="d-flex align-items-center gap-2 mb-2 px-1">
            <span class="small fw-semibold flex-grow-1">Servizio *</span>
            <span class="small fw-semibold" style="width:160px;flex-shrink:0">Prezzo *</span>
            <span class="small fw-semibold" style="width:130px;flex-shrink:0">IVA *</span>
            <span style="width:36px;flex-shrink:0"></span>
        </div>
        <div class="ssv-edit-prices">
            @foreach($variant->prices as $price)
            <div class="ss-edit-price-row" data-price-id="{{ $price->id }}">
                <div class="flex-grow-1"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">
                    <input type="text" class="input-miticko ssv-price-label" value="{{ $price->label }}" placeholder="es. Visita">
                </div></div></div>
                <div style="width:160px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">
                    <input type="number" class="input-miticko ssv-price-value" value="{{ $price->price }}" placeholder="0.00" step="0.01" min="0">
                </div></div></div>
                <div style="width:130px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium"><div class="text-field-container">
                    <select class="input-miticko" name="ssv_price_vat">
                        @foreach($vatOptions as $val => $label)
                            <option value="{{ $val }}" @selected((string)$price->vat_rate === (string)$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div></div></div>
                <div style="width:36px;flex-shrink:0">
                    <button type="button" class="bt-miticko outlined danger small btn-ssv-remove-price"><i class="fa-regular fa-trash-can icon"></i></button>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-2">
            <button type="button" class="bt-miticko bt-m-text-only secondary small btn-ssv-add-price">
                <i class="fa-regular fa-plus icon"></i> Aggiungi componente IVA
            </button>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-3 pt-3 border-top">
            <button type="button" class="bt-miticko outlined secondary small btn-ssv-cancel"><i class="fa-regular fa-times icon"></i> Annulla</button>
            <button type="button" class="bt-miticko small btn-ssv-save" data-mode="buttonSize-Small buttonEmphasis-High">Salva modifiche</button>
        </div>
    </div>
</div>
