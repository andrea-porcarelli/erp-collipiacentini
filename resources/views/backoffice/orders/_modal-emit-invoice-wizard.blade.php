{{-- Wizard "Imposta fattura" (2 step).
     Larghezza: fullscreen responsive (modal-fullscreen-xxl-down nativo di Bootstrap)
     → 100vw/100vh fino a 1399px, poi max 1320px centrata su schermi ≥1400px. --}}
<style>
    /* Compensare il padding 0 globale di .modal-miticko .modal-body:
       le .row Bootstrap con gutter hanno margin negativi laterali che altrimenti sfondano.  */
    #modal-set-invoice .modal-body { overflow-x: hidden; overflow-y: auto; padding: 16px 24px; }
    @media (min-width: 1400px) {
        #modal-set-invoice .modal-dialog { max-width: 1320px; }
        #modal-set-invoice .modal-body { max-height: calc(100vh - 200px); }
    }

    .invoice-wizard-tabs { display: flex; gap: 8px; }
    .invoice-wizard-tabs .bt-miticko { flex: 1; justify-content: center; cursor: pointer; }

    .invoice-wizard-settings-box {
        background: #FAFAFA;
        border: 1px solid #F0F1F5;
        border-radius: 10px;
        padding: 16px;
    }

    .invoice-mode-switch { display: flex; gap: 8px; margin-bottom: 16px; }
    .invoice-mode-switch > * { flex: 1; }

    .invoice-flag-list { display: flex; flex-direction: column; gap: 10px; }
    .invoice-flag-item { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; }
    .invoice-flag-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }

    /* Modalità "Impostazioni di default": campi readonly, visuale greyed-out. */
    .invoice-settings-fields.is-locked {
        opacity: 0.55;
        pointer-events: none;
        user-select: none;
    }
    .invoice-settings-fields.is-locked .text-field,
    .invoice-settings-fields.is-locked .text-field-container,
    .invoice-settings-fields.is-locked select {
        background: #F0F1F5 !important;
        color: #999 !important;
    }
    .invoice-settings-fields.is-locked label span,
    .invoice-settings-fields.is-locked .small { color: #999 !important; }

    .invoice-summary-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .invoice-summary-table th, .invoice-summary-table td { padding: 8px 10px; border-bottom: 1px solid #F0F1F5; }
    .invoice-summary-table thead th { color: #666; font-weight: 600; }
    .invoice-summary-table tfoot td { border-bottom: none; padding-top: 4px; padding-bottom: 4px; }
    .invoice-summary-table tfoot tr.sub td { color: #666; }
    .invoice-summary-table tfoot tr.total td { font-weight: 700; border-top: 1px solid #DDE0E9; padding-top: 10px; }

    .invoice-order-meta { font-size: 13px; }
    .invoice-order-meta .meta-label { color: #666; font-size: 12px; }
    .invoice-order-meta .meta-value { font-weight: 600; }

    .invoice-customer-summary { font-size: 13px; }
    .invoice-customer-summary > div { display: flex; align-items: center; gap: 8px; padding: 2px 0; }
    .invoice-customer-summary i { color: #666; width: 16px; text-align: center; }

    .invoice-open-order-link { color: #FF8A50; font-weight: 600; }
    .invoice-open-order-link:hover { color: #ff7b36; }
    .invoice-wizard-pane h3 { color: var(--card-title, #0D0D0D); font-family: var(--typography-web-title-font, "DM Sans"), sans-serif; font-size: var(--size, 18px);
        font-style: normal;
        font-weight: var(--weight, 700);
        line-height: var(--line-height, 24px); /* 133.333% */
    }
    #modal-set-invoice h1 {
        color: var(--text-main, #0D0D0D);
        font-family: var(--typography-web-title-font, "DM Sans");
        font-size: var(--size, 18px);
        font-style: normal;
        font-weight: var(--weight, 700);
        line-height: var(--line-height, 24px); /* 133.333% */
    }
</style>

<div class="modal fade" tabindex="-1" id="modal-set-invoice">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-xxl-down">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Imposta fattura</h1>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">
                <div id="invoice-wizard-loading" class="text-center py-4 w-100">
                    <i class="fa-regular fa-spinner fa-spin"></i>
                    <div class="small text-secondary mt-2">Calcolo in corso...</div>
                </div>

                <div id="invoice-wizard-content" class="d-none w-100">
                    {{-- Progress-bar step come chip Active/Resting --}}
                    <div class="invoice-wizard-tabs mb-spacing-xl">
                        <x-button label="Imposta fattura" size="Small" emphasis="MediumLow" status="Primary" class="invoice-wizard-tab" :dataset="['step' => '1']" />
                        <x-button label="Riepilogo" status="Disabled" emphasis="High" size="Small" class="invoice-wizard-tab" :dataset="['step' => '2']" />
                    </div>

                    {{-- STEP 1 --}}
                    <section class="invoice-wizard-pane" data-step="1">
                        <div class="row g-4">
                            {{-- Riepilogo ordine --}}
                            <div class="col-12 col-lg-7">
                                <h3 class="mb-3" style="font-size:16px">Riepilogo ordine</h3>
                                <div class="row g-3 mb-3 invoice-order-meta">
                                    <div class="col-6"><div class="meta-label">Partner</div><div class="meta-value" data-field="partner_name">—</div></div>
                                    <div class="col-6"><div class="meta-label">Cliente</div><div class="meta-value" data-field="customer_name">—</div></div>
                                    <div class="col-6"><div class="meta-label">Prodotto</div><div class="meta-value" data-field="product_label">—</div></div>
                                    <div class="col-6"><div class="meta-label">Ordine</div><div class="meta-value" data-field="order_number">—</div></div>
                                    <div class="col-6"><div class="meta-label">Metodo di pagamento</div><div class="meta-value" data-field="payment_method">—</div></div>
                                </div>

                                <table class="invoice-summary-table">
                                    <thead>
                                        <tr>
                                            <th>Prodotto</th>
                                            <th class="text-end">Q.tà</th>
                                            <th class="text-end">Prezzo</th>
                                            <th class="text-end">IVA</th>
                                            <th class="text-end">Subtotale</th>
                                        </tr>
                                    </thead>
                                    <tbody data-slot="order-items"></tbody>
                                    <tfoot>
                                        <tr class="sub"><td colspan="4" class="text-end">Subtotale</td><td class="text-end" data-slot="order-subtotal">—</td></tr>
                                        <tr class="di-cui-label" data-slot="row-order-di-cui-label"><td colspan="5" class="text-start" style="color:#999;font-size:11px;letter-spacing:.5px">DI CUI</td></tr>
                                        <tr class="sub di-cui" data-slot="row-order-commission-payment" style="display:none"><td colspan="3" class="text-start text-secondary">Commissioni di pagamento (<span data-slot="rate-order-payment">—</span>%)</td><td class="text-end" data-slot="vat-order-payment">—</td><td class="text-end" data-slot="amount-order-payment">—</td></tr>
                                        <tr class="sub di-cui" data-slot="row-order-commission-miticko" style="display:none"><td colspan="3" class="text-start text-secondary">Commissioni di Miticko (<span data-slot="rate-order-miticko">—</span>%)</td><td class="text-end" data-slot="vat-order-miticko">—</td><td class="text-end" data-slot="amount-order-miticko">—</td></tr>
                                        <tr class="sub di-cui" data-slot="row-order-presale" style="display:none"><td colspan="3" class="text-start text-secondary">Prevendita (<span data-slot="unit-order-presale">—</span> a biglietto)</td><td class="text-end" data-slot="vat-order-presale">—</td><td class="text-end" data-slot="amount-order-presale">—</td></tr>
                                        <tr class="total"><td colspan="4" class="text-end">Totale ordine</td><td class="text-end" data-slot="order-total">—</td></tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Impostazioni fatturazione --}}
                            <div class="col-12 col-lg-5">
                                <div class="invoice-wizard-settings-box">
                                    <h3 class="mb-3" style="font-size:16px">Impostazioni fatturazione</h3>

                                    <div class="invoice-mode-switch">
                                        <x-button label="Impostazioni di default" size="Small" leading="fa-lock" emphasis="MediumLow" status="Secondary" class="invoice-mode-btn" :dataset="['invoice-mode' => 'default']" />
                                        <x-button label="Personalizza" status="Neutral" emphasis="Low" leading="fa-lock-open" size="Small" class="invoice-mode-btn" :dataset="['invoice-mode' => 'custom']" />
                                    </div>

                                    <div class="invoice-settings-fields">
                                        <div class="mb-3">
                                            <x-select
                                                name="invoice_required"
                                                label="Fattura"
                                                :value="'1'"
                                                :options="[
                                                    ['id' => '1', 'label' => 'Necessaria'],
                                                    ['id' => '0', 'label' => 'Non necessaria'],
                                                ]"
                                            />
                                        </div>

                                        <div class="small fw-semibold mb-2">Campi da inserire in fattura</div>
                                        <div class="invoice-flag-list">
                                            <label class="invoice-flag-item">
                                                <input type="checkbox" class="form-check-input invoice-flag" data-flag="ticket_base">
                                                <span class="small">Biglietto base (non include le eventuali commissioni miticko)</span>
                                            </label>
                                            <label class="invoice-flag-item">
                                                <input type="checkbox" class="form-check-input invoice-flag" data-flag="miticko_commission">
                                                <span class="small">Commissioni Miticko</span>
                                            </label>
                                            <label class="invoice-flag-item">
                                                <input type="checkbox" class="form-check-input invoice-flag" data-flag="presale">
                                                <span class="small">Prevendita Miticko</span>
                                            </label>
                                            <label class="invoice-flag-item">
                                                <input type="checkbox" class="form-check-input invoice-flag" data-flag="bank_commission">
                                                <span class="small">Commissioni gateway di pagamento</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- STEP 2 --}}
                    <section class="invoice-wizard-pane d-none" data-step="2">
                        <div class="row g-4">
                            <div class="col-12 col-lg-7">
                                <h3 class="mb-3" style="font-size:16px">Biglietti</h3>
                                <table class="invoice-summary-table">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th class="text-end">Q.tà</th>
                                            <th class="text-end">Prezzo</th>
                                            <th class="text-end">Subtotale</th>
                                        </tr>
                                    </thead>
                                    <tbody data-slot="invoice-items"></tbody>
                                    <tfoot>
                                        <tr class="sub"><td colspan="3" class="text-end">Subtotale</td><td class="text-end" data-slot="invoice-subtotal">—</td></tr>
                                        <tr class="di-cui-label"><td colspan="4" class="text-start" style="color:#999;font-size:11px;letter-spacing:.5px">DI CUI</td></tr>
                                        <tr class="sub di-cui" data-slot="row-commission-payment" style="display:none"><td colspan="3" class="text-start text-secondary">Commissioni di pagamento (<span data-slot="rate-payment">—</span>%)</td><td class="text-end" data-slot="amount-payment">—</td></tr>
                                        <tr class="sub di-cui" data-slot="row-commission-miticko" style="display:none"><td colspan="3" class="text-start text-secondary">Commissioni di servizio (<span data-slot="rate-miticko">—</span>%)</td><td class="text-end" data-slot="amount-miticko">—</td></tr>
                                        <tr class="sub di-cui" data-slot="row-presale" style="display:none"><td colspan="3" class="text-start text-secondary">Prevendita</td><td class="text-end" data-slot="amount-presale">—</td></tr>
                                        <tr class="total"><td colspan="3" class="text-end">Totale ordine</td><td class="text-end" data-slot="invoice-total">—</td></tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="col-12 col-lg-5">
                                <div class="invoice-wizard-settings-box">
                                    <h3 class="mb-3" style="font-size:16px">Dati documento</h3>

                                    <div class="small text-secondary mb-1">Dati cliente</div>
                                    <div class="invoice-customer-summary mb-3" data-slot="customer-summary">—</div>

                                    <div class="small text-secondary mb-1">Metodo di pagamento</div>
                                    <div class="small mb-3" data-slot="payment-method">—</div>

                                    <a href="#" class="small text-decoration-none invoice-open-order-link" data-slot="order-link" target="_blank">
                                        Apri ordine <i class="fa-regular fa-arrow-right"></i>
                                    </a>

                                    <div class="mt-4">
                                        <x-textarea
                                            name="invoice_description"
                                            label="Descrizione"
                                            :rows="2"
                                            :maxlength="400"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <x-button label="annulla" class="btn-cancel" status="Neutral" emphasis="Low" size="Medium" :dataset="['bs-dismiss' => 'modal']" />
                <x-button label="Salva e vai al riepilogo" trailing="fa-arrow-right" status="Primary" emphasis="High" size="Medium" class="btn-wizard-next" />
                <x-button label="Conferma ed invia" status="Primary" emphasis="High" size="Medium" class="btn-wizard-submit d-none" />
            </div>
        </div>
    </div>
</div>
