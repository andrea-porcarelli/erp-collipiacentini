{{-- Wizard di registrazione ordine manuale, aperto come modale dalla lista ordini. --}}
<style>
    #modal-register-order .modal-dialog { max-width: 1000px; width: 95%; }
    #modal-register-order .modal-body { max-height: calc(100vh - 200px); overflow-y: auto; }

    .register-order-step { position: relative; padding: 12px 0; }
    .register-order-step + .register-order-step { border-top: 1px solid #F0F1F5; margin-top: 8px; }
    .register-order-step.is-locked { opacity: .45; pointer-events: none; }
    .register-order-step .step-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 50%;
        background: #EAEEFA; color: #3948D3; font-weight: 700; font-size: 12px;
        margin-right: 8px;
    }
    .register-order-step .step-badge.done { background: #3948D3; color: #fff; }
    .register-order-step h3 { font-size: 16px; margin-bottom: 12px; }

    #reg-calendar-container .flatpickr-day { font-weight: 700; }
    #reg-calendar-container .flatpickr-day.flatpickr-disabled {
        text-decoration-line: line-through; font-weight: 300; color: #999;
    }
    #reg-calendar-container .flatpickr-day.selected {
        border-radius: 8px !important;
        border: 1px solid #3948D3; background: #3948D3; line-height: 38px;
    }
    #reg-calendar-container .flatpickr-day.today {
        background: #EAEEFA !important; color: #3948D3 !important;
    }

    .reg-slot-list { display: flex; flex-wrap: wrap; gap: 8px; }
    .reg-slot {
        border: 1px solid #DDE0E9; border-radius: 8px; padding: 6px 10px;
        min-width: 82px; cursor: pointer; text-align: center;
    }
    .reg-slot.disabled { opacity: .35; pointer-events: none; }
    .reg-slot.selected { border-color: #3948D3; background: #EAEEFA; }
    .reg-slot-time { font-weight: 700; }
    .reg-slot-avail { font-size: 11px; color: #666; }

    .reg-variant-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 8px 0; border-bottom: 1px solid #F0F1F5;
    }
    .reg-variant-row:last-child { border-bottom: none; }
    .reg-variant-label { flex: 1; }
    .reg-variant-price { font-weight: 700; margin-right: 6px; min-width: 80px; text-align: right; }
    .reg-qty-control { display: flex; align-items: center; gap: 4px; }
    .reg-qty-btn {
        width: 28px; height: 28px; border-radius: 6px; border: 1px solid #DDE0E9;
        background: #fff; cursor: pointer; font-size: 15px;
    }
    .reg-qty-btn:disabled { opacity: .4; cursor: not-allowed; }
    .reg-qty-input { width: 40px; text-align: center; border: 1px solid #DDE0E9; border-radius: 6px; height: 28px; }

    .reg-customer-results {
        border: 1px solid #DDE0E9; border-radius: 8px; max-height: 200px; overflow-y: auto;
        margin-top: 6px; background: #fff;
    }
    .reg-customer-result-item {
        padding: 6px 10px; cursor: pointer; border-bottom: 1px solid #F0F1F5;
    }
    .reg-customer-result-item:last-child { border-bottom: none; }
    .reg-customer-result-item:hover { background: #F7F8FB; }
    .reg-customer-result-item .name { font-weight: 700; }
    .reg-customer-result-item .meta { font-size: 12px; color: #666; }

    .reg-summary-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; }
    .reg-summary-row.total { font-weight: 700; font-size: 16px; margin-top: 6px; padding-top: 8px; border-top: 1px solid #DDE0E9; }
</style>

<div class="modal fade" tabindex="-1" id="modal-register-order">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Registra ordine</h1>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">

                {{-- STEP 1: Partner e prodotto --}}
                <div class="register-order-step" data-step="partner-product">
                    <h3><span class="step-badge" data-role="badge-1">1</span>Partner e prodotto</h3>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="text-field" data-mode="textfieldSize-Medium">
                                <label>Partner *</label>
                                <div class="text-field-container">
                                    <select id="reg-partner" name="partner_id" class="input-miticko">
                                        <option value="">Caricamento…</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-field" data-mode="textfieldSize-Medium">
                                <label>Prodotto *</label>
                                <div class="text-field-container">
                                    <select id="reg-product" name="product_id" class="input-miticko" disabled>
                                        <option value="">Prima seleziona un partner</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Data e orario --}}
                <div class="register-order-step is-locked" data-step="date-slot" id="reg-step-date">
                    <h3><span class="step-badge" data-role="badge-2">2</span>Data e orario</h3>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="mb-2">Data *</label>
                            <div id="reg-calendar-container"></div>
                            <input type="hidden" id="reg-date" name="date" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="mb-2">Orario *</label>
                            <div id="reg-slots" class="reg-slot-list">
                                <div class="text-muted">Seleziona prima una data.</div>
                            </div>
                            <input type="hidden" id="reg-time" name="time" />
                            <input type="hidden" id="reg-slot-type" name="slot_type" />
                            <input type="hidden" id="reg-slot-id" name="slot_id" />
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Biglietti --}}
                <div class="register-order-step is-locked" data-step="variants" id="reg-step-variants">
                    <h3><span class="step-badge" data-role="badge-3">3</span>Biglietti</h3>
                    <div id="reg-variants">
                        <div class="text-muted">Seleziona prima data e orario.</div>
                    </div>
                </div>

                {{-- STEP 4: Cliente --}}
                <div class="register-order-step is-locked" data-step="customer" id="reg-step-customer">
                    <h3><span class="step-badge" data-role="badge-4">4</span>Cliente</h3>

                    <div class="row g-3">
                        <div class="col-12">
                            <x-input name="reg_customer_search" id="reg-customer-search"
                                     label="Cerca cliente esistente"
                                     placeholder="Nome, cognome, email o telefono"
                                     leading="fa-magnifying-glass" />
                            <div id="reg-customer-results" class="reg-customer-results d-none"></div>
                            <input type="hidden" id="reg-customer-id" name="customer[id]" />
                            <div id="reg-customer-selected" class="mt-2 d-none">
                                <span class="text-success"><i class="fa-solid fa-circle-check"></i> Cliente esistente selezionato:</span>
                                <span id="reg-customer-selected-label" class="fw-bold"></span>
                                <button type="button" class="btn btn-link btn-sm" id="reg-customer-deselect">Cambia</button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3" />
                    <p class="text-muted mb-3" style="font-size: 13px;">Oppure inserisci un nuovo cliente (o modifica i dati di quello selezionato):</p>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <x-input name="customer[name]" label="Nome" placeholder="Nome" required />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="customer[surname]" label="Cognome" placeholder="Cognome" required />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="customer[email]" type="email" label="Email" placeholder="email@example.com" required />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="customer[phone]" label="Telefono" placeholder="+39 333 1234567" />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="customer[address]" label="Indirizzo" placeholder="Via / Piazza" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input name="customer[city]" label="Città" placeholder="Città" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input name="customer[zip_code]" label="CAP" placeholder="00000" />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="customer[fiscal_code]" label="Codice fiscale" placeholder="RSSMRA80A01H501U" />
                        </div>
                    </div>
                </div>

                {{-- STEP 5: Riepilogo e pagamento --}}
                <div class="register-order-step is-locked" data-step="summary" id="reg-step-summary">
                    <h3><span class="step-badge" data-role="badge-5">5</span>Riepilogo e pagamento</h3>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="mb-2">Stato pagamento *</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="d-flex align-items-start gap-2">
                                    <input type="radio" name="order_status" value="paid" checked />
                                    <span>
                                        <strong>Pagato offline</strong><br />
                                        <small class="text-muted">L'ordine viene registrato come già pagato (contanti, POS, bonifico).</small>
                                    </span>
                                </label>
                                <label class="d-flex align-items-start gap-2">
                                    <input type="radio" name="order_status" value="pending" />
                                    <span>
                                        <strong>Da pagare</strong><br />
                                        <small class="text-muted">Al termine viene generato un link Stripe da inviare al cliente.</small>
                                    </span>
                                </label>
                            </div>

                            <div class="mt-3">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="reg-send-email" checked />
                                    <span>Invia la mail di conferma al cliente</span>
                                </label>
                                <small class="text-muted d-block">Applicabile solo per ordini "Pagato offline".</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="mb-2">Riepilogo</label>
                            <div id="reg-summary" class="border rounded p-3">
                                <div class="text-muted">Compila i passaggi precedenti.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <x-button label="Annulla" class="btn-cancel" emphasis="Low" :dataset="['bs-dismiss' => 'modal']" />
                <x-button id="reg-submit" label="Registra ordine" status="Primary" emphasis="High" leading="fa-check" />
            </div>
        </div>
    </div>
</div>

{{-- Modal di successo per ordini pending con Payment Link Stripe --}}
<x-modal id="reg-payment-link-modal" title="Ordine registrato" primary="Vai al dettaglio" secondary="Chiudi" width="500px">
    <p>L'ordine è stato registrato correttamente. Copia il link di pagamento qui sotto e invialo al cliente:</p>
    <div class="input-group mt-2">
        <input id="reg-payment-link-url" class="form-control" readonly />
        <button class="btn btn-outline-primary" type="button" id="reg-payment-link-copy">
            <i class="fa-regular fa-copy"></i> Copia
        </button>
    </div>
</x-modal>
