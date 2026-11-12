@props(['model'])

@php
    $selectedIds = $model->resellerPartners->pluck('id')->all();

    $availablePartners = \App\Models\Partner::active()
        ->where('id', '!=', $model->partner_id)
        ->orderBy('partner_name')
        ->get(['id', 'partner_name']);
@endphp

<x-card title="Rivendita prodotti" class="mt-4 position-relative"
    sub_title="Seleziona quali altri partner possono rivendere il tuo prodotto. Potranno impostare i loro testi ed immagini, tuttavia il calendario e le disponibilità rimarranno sincronizzate con le tue: gestirai tu gli orari e le chiusura per tutti.">
    <form id="form-info-resellers">
        <div class="row">
            <div class="col-12" data-mode="typoTitle-Small">
                <h6 class="pre-title-resellers" >
                    Seleziona i partner che possono rivendere il tuo prodotto
                </h6>
                @if($availablePartners->isEmpty())
                    <p class="text-secondary small mb-0">Nessun partner disponibile.</p>
                @else
                    <div class="row g-3 mt-3" data-mode="typoBody-Small">
                        @foreach($availablePartners as $partner)
                            <div class="col-12">
                                <div class="customer-field-item d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                                        <input
                                            type="checkbox"
                                            class="form-check-input customer-field-enabled mt-0"
                                            id="reseller-partner-{{ $partner->id }}"
                                            name="reseller_partner_ids"
                                            value="{{ $partner->id }}"
                                            @checked(in_array($partner->id, $selectedIds))
                                        >
                                        <label class="form-check-label mb-0" for="reseller-partner-{{ $partner->id }}">
                                            {{ $partner->partner_name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </form>
    <div class="button-card-absolute">
        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
    </div>
</x-card>
