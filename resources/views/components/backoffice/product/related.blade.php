@props(['model'])

@php
    $maxRelated = 5;
    $currentCount = $model->relatedProducts()->count();
@endphp

{{-- Lista prodotti correlati --}}
<div id="related-list" class="mb-4">
    @forelse($model->relatedProducts()->with('relatedProduct')->get() as $related)
        <div class="related-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="{{ $related->id }}">
            <span class="fw-semibold flex-grow-1">{{ $related->relatedProduct?->label }}</span>
            <span class="text-secondary small">{{ $related->relatedProduct?->product_code }}</span>
            <x-button emphasis="outlined" status="danger" size="small" leading="fa-trash" class="btn-related-delete" />
        </div>
    @empty
        <p class="text-secondary small mb-0" id="related-empty">Nessun prodotto correlato aggiunto.</p>
    @endforelse
</div>

<div class="border-top pt-3" id="related-add-section" @if($currentCount >= $maxRelated) style="display:none" @endif>
    <p class="fw-semibold small mb-3">Aggiungi prodotto correlato <span class="text-secondary fw-normal">(max {{ $maxRelated }})</span></p>
    <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-8">
            <div class="text-field" data-mode="medium">
                <label>Cerca prodotto</label>
                <div class="text-field-container">
                    <input id="related-search-input" class="input-miticko" type="text" placeholder="Digita per cercare..." autocomplete="off" />
                </div>
            </div>
            <div id="related-search-results" class="list-group mt-1" style="display:none;max-height:220px;overflow-y:auto;position:relative;z-index:10;"></div>
        </div>
        <div class="col-12 col-sm-4">
            <x-button id="btn-related-add" emphasis="primary" status="success" size="small" leading="fa-plus" label="Aggiungi" />
        </div>
    </div>
</div>

@if($currentCount >= $maxRelated)
    <p class="text-secondary small mt-2 mb-0" id="related-limit-msg">Hai raggiunto il limite massimo di {{ $maxRelated }} prodotti correlati.</p>
@endif
