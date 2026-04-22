@props(['model', 'features'])
@php
    $selectedIds = $model->features->pluck('id')->all();
@endphp

<x-card title="Caratteristiche del prodotto" sub_title="Seleziona le caratteristiche che descrivono il prodotto, verranno mostrate all'utente nella pagina di dettaglio" class="mt-4 position-relative">
    <form id="form-info-features">
        <div class="row">
            @foreach($features as $category => $items)
                <div class="col-12 col-sm-6 mb-3">
                    <h6 class="text-uppercase small text-secondary mb-2">
                        {{ \App\Models\ProductFeature::CATEGORIES[$category] ?? $category }}
                    </h6>
                    @foreach($items as $feature)
                        <label class="d-flex align-items-center gap-2 mb-1" style="cursor: pointer;">
                            <input
                                type="checkbox"
                                name="features"
                                value="{{ $feature->id }}"
                                @checked(in_array($feature->id, $selectedIds))
                            >
                            @if($feature->icon)
                                <i class="fa-regular {{ $feature->icon }} text-secondary"></i>
                            @endif
                            <span>{{ $feature->translated_label }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </form>
    <div class="button-card-absolute">
        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
    </div>
</x-card>
