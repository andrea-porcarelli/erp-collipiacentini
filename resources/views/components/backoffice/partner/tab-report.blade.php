@props(['model'])

@php
    $authUser = Auth::user();
    $isGod = $authUser->role === 'god';
@endphp

<div class="tab-pane fade switch-container" id="partner-report-panel" role="tabpanel" data-partner-id="{{ $model->id }}">

    {{-- CARD 1: Impostazioni di invio (sempre visibile; Partner solo in lettura) --}}
    <x-card title="Impostazioni di invio" class="position-relative card-report-settings">
        <form id="form-partner-report-settings" class="py-3">
            <div class="row align-items-center">
                <div class="col-12 col-md-9">
                    <div class="fw-semibold mb-1">Invio automatico</div>
                    <p class="text-secondary mb-0 small">Report del mese precedente, inviato il 5 del mese successivo</p>
                </div>
                <div class="col-12 col-md-3 text-md-end mt-3 mt-md-0">
                    <div class="switch-cell switch-cell-settings">
                        <x-switch
                            name="report_auto_send_enabled"
                            class="switch-report-auto-send"
                            :checked="(bool) $model->report_auto_send_enabled"
                            :disabled="! $isGod" />
                    </div>
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card btn-save-report-settings" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    {{-- CARD 2: Registro di invio dei Report --}}
    <x-card title="Registro di invio dei Report" sub_title="Verifica lo storico di invio dei report sia automatico che manuale. Puoi inoltre scaricare i file originali che ti sono stati inviati via email" class="mt-4 position-relative">
        <div class="table-responsive">
            <table id="dt-report-logs"
                   class="table table-miticko align-middle w-100"
                   data-url="{{ route('partners.report.data.logs', $model->id) }}">
                <thead>
                    <tr>
                        <th data-name="pr_year">Periodo</th>
                        <th data-name="email">Destinatario</th>
                        <th data-name="sent_at">Data invio</th>
                        <th data-name="pr_mode">Modalità</th>
                        <th data-name="status">Stato</th>
                        <th data-name="download" class="text-end">Download</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- CARD 3: Impostazione di ricezione dei Report --}}
    <x-card title="Impostazione di ricezione dei Report" sub_title="Scegli quali utenti possono ricevere i report mensili via email" class="mt-4 position-relative">

        @if($isGod)
            <div class="fw-semibold mb-2">Account Miticko</div>
            <div class="table-responsive mb-4">
                <table id="dt-report-subscribers-miticko"
                       class="table table-miticko align-middle w-100"
                       data-url="{{ route('partners.report.data.subscribers-miticko', $model->id) }}">
                    <thead>
                        <tr>
                            <th data-name="email">Utente</th>
                            <th data-name="role">Ruolo</th>
                            <th data-name="toggle" class="text-end">Invio automatico</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        @endif

        <div class="fw-semibold mb-2">Account Partner</div>
        <div class="table-responsive">
            <table id="dt-report-subscribers-partner"
                   class="table table-miticko align-middle w-100"
                   data-url="{{ route('partners.report.data.subscribers-partner', $model->id) }}">
                <thead>
                    <tr>
                        <th data-name="email">Utente</th>
                        <th data-name="role">Ruolo</th>
                        <th data-name="toggle" class="text-end">Invio automatico</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="button-card-absolute">
            <x-button class="btn-save-card btn-save-report-subscriptions" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
