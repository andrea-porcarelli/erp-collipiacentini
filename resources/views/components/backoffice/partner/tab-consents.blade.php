@props(['model'])

@php
    $languages = \App\Models\Language::where('is_active', 1)->orderBy('iso_code')->get();
    $consents  = $model->consents()->withCount('customerConsents')->orderBy('position')->get();
    $isEnabled = (bool) $model->consents_enabled;

    $dayOpts   = collect(range(0, 31))->map(fn($n) => ['id' => $n, 'label' => (string) $n])->all();
    $monthOpts = collect(range(0, 12))->map(fn($n) => ['id' => $n, 'label' => (string) $n])->all();
    $yearOpts  = collect(range(0, 50))->map(fn($n) => ['id' => $n, 'label' => (string) $n])->all();

    $langOpts  = $languages->map(fn($l) => ['id' => $l->iso_code, 'label' => $l->label])->all();
@endphp

<div class="tab-pane fade" id="partner-consents-panel" role="tabpanel">
    @if(! $isEnabled)
        <x-card title="Consensi utente" sub_title="Checkbox di consenso che il cliente accetta in fase di acquisto" class="position-relative">
            <div class="text-center py-5">
                <i class="fa-regular fa-shield-check fa-2x text-secondary mb-3 d-block"></i>
                <p class="text-secondary mb-3">La sezione è disabilitata.</p>
                <x-button class="btn-consents-enable" label="Abilita consensi" leading="fa-toggle-on" emphasis="High" />
            </div>
        </x-card>
    @else
        <div id="consents-list" data-partner-id="{{ $model->id }}">
            @foreach($consents as $c)
                @include('components.backoffice.partner._consent-item', [
                    'consent'    => $c,
                    'languages'  => $languages,
                    'dayOpts'    => $dayOpts,
                    'monthOpts'  => $monthOpts,
                    'yearOpts'   => $yearOpts,
                    'langOpts'   => $langOpts,
                ])
            @endforeach
        </div>

        <div class="mt-3">
            <x-button status="Secondary" emphasis="Low" label="Aggiungi consenso" leading="fa-plus" class="btn-consent-add" />
        </div>

        <template id="consent-item-template">
            @include('components.backoffice.partner._consent-item', [
                'consent'    => null,
                'languages'  => $languages,
                'dayOpts'    => $dayOpts,
                'monthOpts'  => $monthOpts,
                'yearOpts'   => $yearOpts,
                'langOpts'   => $langOpts,
            ])
        </template>
    @endif
</div>
