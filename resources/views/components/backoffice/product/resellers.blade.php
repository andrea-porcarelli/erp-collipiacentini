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
            <div class="col-12">
                <h6 class="text-uppercase small text-secondary mb-2">
                    Seleziona i partner che possono rivendere il tuo prodotto
                </h6>
                @if($availablePartners->isEmpty())
                    <p class="text-secondary small mb-0">Nessun partner disponibile.</p>
                @else
                    @foreach($availablePartners as $partner)
                        <label class="d-flex align-items-center gap-2 mb-1" style="cursor: pointer;">
                            <input
                                type="checkbox"
                                name="reseller_partner_ids"
                                value="{{ $partner->id }}"
                                @checked(in_array($partner->id, $selectedIds))
                            >
                            <span>{{ $partner->partner_name }}</span>
                        </label>
                    @endforeach
                @endif
            </div>
        </div>
    </form>
    <div class="button-card-absolute">
        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
    </div>
</x-card>
