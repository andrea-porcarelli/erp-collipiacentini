{{-- Wizard "Imposta fattura" (2 step), largo come la modale register-order per ospitare le 2 colonne. --}}
<style>
    #modal-set-invoice .modal-dialog { max-width: 1100px; width: 95%; }
    #modal-set-invoice .modal-body { max-height: calc(100vh - 200px); overflow-y: auto; }

    .invoice-wizard-tabs { display: flex; gap: 8px; }
    .invoice-wizard-tabs .chip-miticko { flex: 1; justify-content: center; cursor: pointer; }

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
</style>

<div class="modal fade" tabindex="-1" id="modal-set-invoice">
    <div class="modal-dialog modal-dialog-scrollable">
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
                        <x-chip label="Imposta fattura" appearance="Active" class="invoice-wizard-tab" :dataset="['step' => '1']" />
                        <x-chip label="Riepilogo" appearance="Resting" class="invoice-wizard-tab" :dataset="['step' => '2']" />
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
                                        <tr class="total"><td colspan="4" class="text-end">Totale ordine</td><td class="text-end" data-slot="order-total">—</td></tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Impostazioni fatturazione --}}
                            <div class="col-12 col-lg-5">
                                <div class="invoice-wizard-settings-box">
                                    <h3 class="mb-3" style="font-size:16px">Impostazioni fatturazione</h3>

                                    <div class="invoice-mode-switch">
                                        <x-button label="Impostazioni di default" status="Primary" emphasis="Medium" leading="fa-lock" size="Small" class="invoice-mode-btn" :dataset="['mode' => 'default']" />
                                        <x-button label="Personalizza" status="Neutral" emphasis="Low" leading="fa-lock-open" size="Small" class="invoice-mode-btn" :dataset="['mode' => 'custom']" />
                                    </div>

                                    <div class="mb-3">
                                        <x-select
                                            id="invoice-required-select"
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
                    </section>

                    {{-- STEP 2 --}}
                    <section class="invoice-wizard-pane d-none" data-step="2">
                        <div class="row g-4">
                            <div class="col-12 col-lg-7">
                                <h3 class="mb-3" style="font-size:16px">Descrizione</h3>
                                <table class="invoice-summary-table">
                                    <thead>
                                        <tr>
                                            <th>Prodotto</th>
                                            <th class="text-end">Q.tà</th>
                                            <th class="text-end">Prezzo</th>
                                            <th class="text-end">Sconto</th>
                                            <th class="text-end">IVA</th>
                                            <th class="text-end">Subtotale</th>
                                        </tr>
                                    </thead>
                                    <tbody data-slot="invoice-items"></tbody>
                                    <tfoot>
                                        <tr class="sub"><td colspan="5" class="text-end">Subtotale</td><td class="text-end" data-slot="invoice-subtotal">—</td></tr>
                                        <tr class="sub"><td colspan="5" class="text-end">IVA</td><td class="text-end" data-slot="invoice-vat">—</td></tr>
                                        <tr class="total"><td colspan="5" class="text-end">Totale fattura</td><td class="text-end" data-slot="invoice-total">—</td></tr>
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
                <x-button label="annulla" class="btn-cancel" status="Neutral" emphasis="Low" :dataset="['bs-dismiss' => 'modal']" />
                <x-button label="Salva e vai al riepilogo" trailing="fa-arrow-right" status="Primary" emphasis="High" class="btn-wizard-next" />
                <x-button label="Conferma ed invia" status="Primary" emphasis="High" class="btn-wizard-submit d-none" />
            </div>
        </div>
    </div>
</div>
