@props(['model', 'features'])
@php
    $selectedIds = $model->features->pluck('id')->all();
@endphp

<x-card title="Servizi disponibili" sub_title="seleziona tutti i servizi diponibili, verranno visualizzati nella pagina prodotto" class="mt-4 position-relative">
    <form id="form-info-features">
        <div class="row">
            @foreach($features as $category => $items)
                <div class="col-12 mb-3">
                    <h6 class="mb-2 fw-bold feature-title">
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
                            <span class="feature-text" data-mode="typoBody-Medium">{{ $feature->translated_label }}</span>
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
