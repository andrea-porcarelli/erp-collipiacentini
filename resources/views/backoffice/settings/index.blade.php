@extends('backoffice.layout', ['title' => 'Impostazioni', 'active' => 'admin-settings'])

@section('main-content')
    <x-header-page title="Impostazioni" />

    <div class="w-100">
        <div class="d-flex gap-2 product-tabs-scroll" id="settingsTabs" role="tablist">
            <x-chip label="Generali" :dataset="['tab-target' => '#settings-general-panel']" />
            <x-chip label="Gestione Aliquote IVA" appearance="Resting" :dataset="['tab-target' => '#settings-vat-panel']" />
        </div>

        <div class="tab-content mt-spacing-xl" id="settingsTabsContent">

            {{-- TAB GENERALI --}}
            <div class="tab-pane fade show active" id="settings-general-panel" role="tabpanel">
                <x-card title="Dati di fatturazione Miticko" sub_title="Dati fiscali del cedente/prestatore utilizzati per generare la fattura elettronica (XML FatturaPA) verso i Partner. I valori sono definiti nel file <code>config/fatturapa.php</code> e letti da variabili d'ambiente (<code>FPA_MITICKO_*</code>).">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <x-input label="Ragione sociale" :value="$miticko['organization']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Regime fiscale" :value="$miticko['tax_regime']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Partita IVA" :value="$miticko['vat_number']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Codice fiscale" :value="$miticko['fiscal_code']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Paese" :value="$miticko['country_code']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Codice destinatario SdI (trasmittente)" :value="$miticko['sender_vat_id']" disabled />
                        </div>

                        <div class="col-12 mt-3">
                            <h6 data-mode="typoTitle-Small" class="mb-2">Sede legale</h6>
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Indirizzo" :value="$miticko['address']['street']" disabled />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input label="Numero civico" :value="$miticko['address']['street_number']" disabled />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input label="CAP" :value="$miticko['address']['zip']" disabled />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input label="Città" :value="$miticko['address']['city']" disabled />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input label="Provincia" :value="$miticko['address']['province']" disabled />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input label="Paese sede" :value="$miticko['address']['country_code']" disabled />
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- TAB VAT --}}
            <div class="tab-pane fade" id="settings-vat-panel" role="tabpanel" style="display:none">
                <x-card title="Gestione Aliquote IVA" sub_title="Aliquote numeriche e codici natura (SdI / FatturaPA) disponibili nel sistema. Le aliquote numeriche vengono proposte nelle varianti prezzo; i codici natura sono riservati alle righe di fatturazione dove l'IVA non è dovuta." class="position-relative">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi aliquota" status="Primary" leading="fa-plus" class="btn-add-vat-rate" />
                    </div>

                    <div class="table-responsive" data-mode="table-Default">
                        <table class="table table-miticko align-middle w-100">
                            <thead>
                                <tr>
                                    <th data-mode="typoTitle-Small">Codice</th>
                                    <th data-mode="typoTitle-Small">Etichetta</th>
                                    <th data-mode="typoTitle-Small" class="text-end">Aliquota %</th>
                                    <th data-mode="typoTitle-Small">Natura SdI</th>
                                    <th data-mode="typoTitle-Small">Stato</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody id="vat-rates-tbody">
                                @foreach($vatRates as $v)
                                    <tr data-id="{{ $v->id }}"
                                        data-code="{{ $v->code }}"
                                        data-label="{{ $v->label }}"
                                        data-rate="{{ $v->rate }}"
                                        data-nature="{{ $v->nature }}"
                                        data-is-active="{{ (int) $v->is_active }}">
                                        <td data-mode="typoBody-Small"><code>{{ $v->code }}</code></td>
                                        <td data-mode="typoBody-Small">{{ $v->label }}</td>
                                        <td data-mode="typoBody-Small" class="text-end">
                                            {{ $v->rate !== null ? number_format((float) $v->rate, 2).' %' : '—' }}
                                        </td>
                                        <td data-mode="typoBody-Small">{{ $v->nature ?: '—' }}</td>
                                        <td data-mode="typoBody-Small">
                                            @if($v->is_active)
                                                <span class="badge bg-success-subtle text-success">Attiva</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Disattiva</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <x-icon-button class="btn-edit-vat-rate" icon="fa-pen" appearance="Neutral" emphasis="Medium" />
                                                @if(in_array($v->id, $inUseIds, true))
                                                    <x-icon-button class="btn-delete-vat-rate disabled" icon="fa-trash-can" appearance="Disabled" emphasis="Medium"
                                                                   title="Aliquota in uso da una o più varianti prezzo: non può essere eliminata. Disattivala invece." />
                                                @else
                                                    <x-icon-button class="btn-delete-vat-rate" icon="fa-trash-can" appearance="Neutral" emphasis="Medium" />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    {{-- Modal: crea/modifica aliquota IVA --}}
    <x-modal id="modal-vat-rate" title="Aliquota IVA" primary="Salva" secondary="Annulla" width="600px">
        <form id="form-vat-rate" class="w-100">
            <input type="hidden" name="id" value="">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <x-input name="code" label="Codice *" placeholder="es. 22 oppure N3.4" required
                             message="Chiave stabile usata internamente e per la fattura elettronica" />
                </div>
                <div class="col-12 col-md-8">
                    <x-input name="label" label="Etichetta *" placeholder="es. 22% – Aliquota ordinaria" required />
                </div>
                <div class="col-12 col-md-6">
                    <x-input name="rate" type="number" step="0.01" min="0" max="100" label="Aliquota %"
                             message="Valore numerico per il calcolo dell'IVA. Lascia vuoto per i codici natura puri (N1, N2.1, …)." />
                </div>
                <div class="col-12 col-md-6">
                    <x-input name="nature" label="Natura SdI" placeholder="es. N3.4"
                             message="Codice natura FatturaPA per operazioni non imponibili / esenti / reverse charge" />
                </div>
                <div class="col-12">
                    <label class="d-inline-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input" name="is_active" id="vat-is-active" value="1" checked>
                        <span>Attiva (visibile nelle select)</span>
                    </label>
                </div>
            </div>
        </form>
    </x-modal>
@endsection

@section('custom-script')
    <script>
        $(function () {

            const $modal = $('#modal-vat-rate');
            const $form  = $('#form-vat-rate');

            const resetForm = () => {
                $form[0].reset();
                $form.find('[name="id"]').val('');
                $modal.find('.modal-title').text('Aggiungi aliquota IVA');
            };

            const fillForm = (row) => {
                $form.find('[name="id"]').val(row.id);
                $form.find('[name="code"]').val(row.code);
                $form.find('[name="label"]').val(row.label);
                $form.find('[name="rate"]').val(row.rate ?? '');
                $form.find('[name="nature"]').val(row.nature ?? '');
                $form.find('[name="is_active"]').prop('checked', !!row.isActive);
                $modal.find('.modal-title').text('Modifica aliquota IVA');
            };

            $(document).on('click', '.btn-add-vat-rate', function () {
                resetForm();
                $modal.modal('show');
            });

            $(document).on('click', '.btn-edit-vat-rate', function () {
                const $tr = $(this).closest('tr');
                fillForm({
                    id: $tr.data('id'),
                    code: $tr.data('code'),
                    label: $tr.data('label'),
                    rate: $tr.data('rate'),
                    nature: $tr.data('nature'),
                    isActive: Number($tr.data('is-active')) === 1,
                });
                $modal.modal('show');
            });

            $(document).on('click', '#modal-vat-rate .btn-success', function () {
                const id = $form.find('[name="id"]').val();
                const data = {
                    code: $form.find('[name="code"]').val().trim(),
                    label: $form.find('[name="label"]').val().trim(),
                    rate: $form.find('[name="rate"]').val() !== '' ? parseFloat($form.find('[name="rate"]').val()) : null,
                    nature: $form.find('[name="nature"]').val().trim() || null,
                    is_active: $form.find('[name="is_active"]').is(':checked') ? 1 : 0,
                };

                if (!data.code || !data.label) {
                    toastr.warning('Codice ed etichetta sono obbligatori');
                    return;
                }

                const path = id ? `/vat-rates/${id}` : `/vat-rates`;
                const method = id ? 'put' : 'post';

                $(document).trigger('fetch', [{
                    path, method, data,
                    then: () => {
                        toastr.success('Aliquota salvata');
                        setTimeout(() => location.reload(), 500);
                    },
                    catch: (xhr) => {
                        const msg = xhr.responseJSON?.message
                                 || Object.values(xhr.responseJSON?.errors ?? {})[0]?.[0]
                                 || 'Errore durante il salvataggio';
                        toastr.error(msg);
                    },
                }]);
            });

            $(document).on('click', '.btn-delete-vat-rate', function () {
                if ($(this).hasClass('disabled')) {
                    toastr.warning('Aliquota in uso: non è eliminabile. Disattivala dal pulsante di modifica.');
                    return;
                }
                const $tr = $(this).closest('tr');
                const id = $tr.data('id');
                const label = $tr.data('label');

                $(document).trigger('sweetConfirmTrigger', [{
                    title: 'Elimina aliquota',
                    text: `Vuoi davvero eliminare "${label}"?`,
                    callback: () => {
                        $(document).trigger('fetch', [{
                            path: `/vat-rates/${id}`,
                            method: 'delete',
                            then: () => {
                                $tr.remove();
                                toastr.success('Aliquota eliminata');
                            },
                            catch: (xhr) => {
                                toastr.error(xhr.responseJSON?.message || 'Errore durante l\'eliminazione');
                            },
                        }]);
                    },
                }]);
            });

            // Tabs chip-based (stesso pattern di products.show)
            (function () {
                const chips = document.querySelectorAll('#settingsTabs .chip-miticko');
                const panes = document.querySelectorAll('#settingsTabsContent .tab-pane');
                function showTab(target) {
                    panes.forEach(p => { p.style.display = 'none'; p.style.opacity = '0'; });
                    const active = document.querySelector(target);
                    if (active) { active.style.display = 'block'; active.style.opacity = '1'; }
                }
                chips.forEach(chip => chip.addEventListener('click', function () {
                    const target = this.getAttribute('data-tab-target');
                    if (!target) return;
                    showTab(target);
                    chips.forEach(c => c.setAttribute('data-mode', 'chipAppearance-Resting'));
                    this.setAttribute('data-mode', 'chipAppearance-Active');
                }));
            })();
        });
    </script>
@endsection
