@extends('backoffice.layout', ['title' => 'Registra ordine', 'active' => $path])

@section('main-content')
    <x-header-page title="Registra ordine" />

    @section('custom-css')
    <style>
        .register-order-step { position: relative; }
        .register-order-step.is-locked { opacity: .45; pointer-events: none; }
        .register-order-step .step-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%;
            background: #EAEEFA; color: #3948D3; font-weight: 700; font-size: 13px;
            margin-right: 10px;
        }
        .register-order-step .step-badge.done { background: #3948D3; color: #fff; }

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

        .reg-slot-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .reg-slot {
            border: 1px solid #DDE0E9; border-radius: 8px; padding: 8px 12px;
            min-width: 96px; cursor: pointer; text-align: center;
            transition: border-color .1s, background .1s;
        }
        .reg-slot.disabled { opacity: .35; pointer-events: none; }
        .reg-slot.selected { border-color: #3948D3; background: #EAEEFA; }
        .reg-slot .reg-slot-time { font-weight: 700; }
        .reg-slot .reg-slot-avail { font-size: 12px; color: #666; }

        .reg-variant-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 10px 0; border-bottom: 1px solid #F0F1F5;
        }
        .reg-variant-row:last-child { border-bottom: none; }
        .reg-variant-label { flex: 1; }
        .reg-variant-price { font-weight: 700; margin-right: 8px; min-width: 90px; text-align: right; }
        .reg-qty-control { display: flex; align-items: center; gap: 6px; }
        .reg-qty-btn {
            width: 30px; height: 30px; border-radius: 6px; border: 1px solid #DDE0E9;
            background: #fff; cursor: pointer; font-size: 16px;
        }
        .reg-qty-btn:disabled { opacity: .4; cursor: not-allowed; }
        .reg-qty-input { width: 44px; text-align: center; border: 1px solid #DDE0E9; border-radius: 6px; height: 30px; }

        .reg-customer-results {
            border: 1px solid #DDE0E9; border-radius: 8px; max-height: 240px; overflow-y: auto;
            margin-top: 6px; background: #fff;
        }
        .reg-customer-result-item {
            padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #F0F1F5;
        }
        .reg-customer-result-item:last-child { border-bottom: none; }
        .reg-customer-result-item:hover { background: #F7F8FB; }
        .reg-customer-result-item .name { font-weight: 700; }
        .reg-customer-result-item .meta { font-size: 12px; color: #666; }

        .reg-summary-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .reg-summary-row.total { font-weight: 700; font-size: 18px; margin-top: 8px; padding-top: 10px; border-top: 1px solid #DDE0E9; }
    </style>
    @endsection

    <div class="w-100">
        <div class="row g-3">
            {{-- STEP 1 & 2: Partner + Prodotto --}}
            <div class="col-12">
                <x-card>
                    <div class="register-order-step" data-step="partner-product">
                        <h3 class="mb-3"><span class="step-badge" data-role="badge-1">1</span>Partner e prodotto</h3>
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
                </x-card>
            </div>

            {{-- STEP 3: Data & Slot --}}
            <div class="col-12">
                <x-card>
                    <div class="register-order-step is-locked" data-step="date-slot" id="reg-step-date">
                        <h3 class="mb-3"><span class="step-badge" data-role="badge-2">2</span>Data e orario</h3>
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
                </x-card>
            </div>

            {{-- STEP 4: Biglietti --}}
            <div class="col-12">
                <x-card>
                    <div class="register-order-step is-locked" data-step="variants" id="reg-step-variants">
                        <h3 class="mb-3"><span class="step-badge" data-role="badge-3">3</span>Biglietti</h3>
                        <div id="reg-variants">
                            <div class="text-muted">Seleziona prima data e orario.</div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- STEP 5: Cliente --}}
            <div class="col-12">
                <x-card>
                    <div class="register-order-step is-locked" data-step="customer" id="reg-step-customer">
                        <h3 class="mb-3"><span class="step-badge" data-role="badge-4">4</span>Cliente</h3>

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
                        <p class="text-muted mb-3">Oppure inserisci un nuovo cliente (o modifica i dati di quello selezionato):</p>

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
                </x-card>
            </div>

            {{-- STEP 6: Riepilogo e pagamento --}}
            <div class="col-12">
                <x-card>
                    <div class="register-order-step is-locked" data-step="summary" id="reg-step-summary">
                        <h3 class="mb-3"><span class="step-badge" data-role="badge-5">5</span>Riepilogo e pagamento</h3>

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
                                            <small class="text-muted">Al termine viene generato un link di pagamento Stripe da inviare al cliente.</small>
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

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="{{ url('/orders') }}" class="text-decoration-none">
                                <x-button label="Annulla" status="Neutral" emphasis="Medium" />
                            </a>
                            <x-button id="reg-submit" label="Registra ordine" status="Primary" leading="fa-check" />
                        </div>
                    </div>
                </x-card>
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
@endsection

@section('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script>
        window.orderCreateRoutes = {
            partners:          @json(route('orders.create.partners')),
            products:          @json(route('orders.create.products')),
            availabilityDays:  @json(route('orders.create.availabilityDays')),
            availabilitySlots: @json(route('orders.create.availabilitySlots')),
            variants:          @json(route('orders.create.variants')),
            customers:         @json(route('orders.create.customers')),
            store:             @json(route('orders.store')),
            ordersIndex:       @json(route('orders.index')),
            paymentLinkTpl:    @json(url('/orders')) + '/{order}/payment-link',
        };
    </script>
    <script type="module" src="{{ asset('backoffice/js/order-create.js') }}?v={{ filemtime(public_path('backoffice/js/order-create.js')) }}"></script>
@endsection
