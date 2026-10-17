<div class="modal" tabindex="-1" id="modal-set-invoice">
    <div class="modal-dialog modal-lg" style="max-width: 920px">
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
                    {{-- Progress-bar step --}}
                    <div class="invoice-wizard-steps d-flex gap-2 mb-3">
                        <div class="invoice-wizard-step active flex-grow-1" data-step="1">
                            <i class="fa-regular fa-file-invoice"></i>
                            <span>Imposta fattura</span>
                        </div>
                        <div class="invoice-wizard-step flex-grow-1" data-step="2">
                            <i class="fa-regular fa-eye"></i>
                            <span>Riepilogo</span>
                        </div>
                    </div>

                    {{-- STEP 1 --}}
                    <section class="invoice-wizard-pane" data-step="1">
                        <div class="row g-3">
                            <div class="col-12 col-lg-7">
                                <div class="small fw-semibold mb-2">Riepilogo ordine</div>
                                <div class="row g-2 small mb-3">
                                    <div class="col-6"><div class="text-secondary">Partner</div><div class="fw-semibold" data-field="partner_name">—</div></div>
                                    <div class="col-6"><div class="text-secondary">Cliente</div><div class="fw-semibold" data-field="customer_name">—</div></div>
                                    <div class="col-6"><div class="text-secondary">Prodotto</div><div class="fw-semibold" data-field="product_label">—</div></div>
                                    <div class="col-6"><div class="text-secondary">Ordine</div><div class="fw-semibold" data-field="order_number">—</div></div>
                                    <div class="col-6"><div class="text-secondary">Metodo di pagamento</div><div class="fw-semibold" data-field="payment_method">—</div></div>
                                </div>

                                <table class="invoice-wizard-table w-100 small">
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
                                        <tr class="totals"><td colspan="4" class="text-end text-secondary">Subtotale</td><td class="text-end" data-slot="order-subtotal">—</td></tr>
                                        <tr class="totals"><td colspan="4" class="text-end text-secondary">Totale ordine</td><td class="text-end fw-bold" data-slot="order-total">—</td></tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="col-12 col-lg-5">
                                <div class="invoice-wizard-settings">
                                    <div class="small fw-semibold mb-2">Impostazioni fatturazione</div>
                                    <div class="btn-group w-100 mb-3" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary invoice-mode-btn active" data-mode="default">
                                            <i class="fa-regular fa-list"></i> Impostazioni di default
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary invoice-mode-btn" data-mode="custom">
                                            <i class="fa-regular fa-pen"></i> Personalizza
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small text-secondary d-block mb-1">Fattura</label>
                                        <select class="input-miticko form-select" id="invoice-required-select">
                                            <option value="1">Necessaria</option>
                                            <option value="0">Non necessaria</option>
                                        </select>
                                    </div>

                                    <div class="small fw-semibold mb-2">Campi da inserire in fattura</div>
                                    <div class="d-flex flex-column gap-2 invoice-flags-list">
                                        <label class="d-inline-flex align-items-center gap-2">
                                            <input type="checkbox" class="form-check-input invoice-flag" data-flag="ticket_base">
                                            <span class="small">Biglietto base (non include le eventuali commissioni miticko)</span>
                                        </label>
                                        <label class="d-inline-flex align-items-center gap-2">
                                            <input type="checkbox" class="form-check-input invoice-flag" data-flag="miticko_commission">
                                            <span class="small">Commissioni Miticko</span>
                                        </label>
                                        <label class="d-inline-flex align-items-center gap-2">
                                            <input type="checkbox" class="form-check-input invoice-flag" data-flag="presale">
                                            <span class="small">Prevendita Miticko</span>
                                        </label>
                                        <label class="d-inline-flex align-items-center gap-2">
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
                        <div class="row g-3">
                            <div class="col-12 col-lg-7">
                                <div class="small fw-semibold mb-2">Descrizione</div>
                                <table class="invoice-wizard-table w-100 small">
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
                                        <tr class="totals"><td colspan="5" class="text-end text-secondary">Subtotale</td><td class="text-end" data-slot="invoice-subtotal">—</td></tr>
                                        <tr class="totals"><td colspan="5" class="text-end text-secondary">IVA</td><td class="text-end" data-slot="invoice-vat">—</td></tr>
                                        <tr class="totals"><td colspan="5" class="text-end fw-bold">Totale fattura</td><td class="text-end fw-bold" data-slot="invoice-total">—</td></tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="col-12 col-lg-5">
                                <div class="invoice-wizard-settings">
                                    <div class="small fw-semibold mb-3">Dati documento</div>
                                    <div class="small text-secondary mb-1">Dati cliente</div>
                                    <div class="customer-summary small mb-3" data-slot="customer-summary">—</div>

                                    <div class="small text-secondary mb-1">Metodo di pagamento</div>
                                    <div class="small mb-3" data-slot="payment-method">—</div>

                                    <a href="#" class="small text-decoration-none" data-slot="order-link" target="_blank">Apri ordine →</a>

                                    <div class="mt-3">
                                        <label class="small text-secondary d-block mb-1">Descrizione</label>
                                        <textarea class="input-miticko form-control" id="invoice-description" rows="2" maxlength="400"></textarea>
                                        <div class="small text-secondary mt-1"><span id="invoice-description-count">400</span> caratteri rimanenti</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <x-button label="annulla" class="btn-cancel" type="default" emphasis="Low" :dataset="['bs-dismiss' => 'modal']"/>
                <x-button label="Salva e vai al riepilogo →" class="btn-wizard-next" emphasis="High" />
                <x-button label="Conferma ed invia" class="btn-wizard-submit d-none" emphasis="High" />
            </div>
        </div>
    </div>
</div>

@push('styles')
@endpush
