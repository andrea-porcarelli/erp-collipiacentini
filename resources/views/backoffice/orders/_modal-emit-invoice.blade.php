<x-modal id="modal-emit-invoice" title="Emetti fattura" primary="Emetti fatture selezionate" secondary="Annulla" width="780px">
    <div id="invoice-loading" class="text-center py-4 w-100">
        <i class="fa-regular fa-spinner fa-spin"></i>
        <div class="small text-secondary mt-2">Calcolo in corso...</div>
    </div>

    <div id="invoice-content" class="d-none w-100">
        <div class="invoice-order-summary mb-3">
            Ordine <strong>#{{ $order->order_number }}</strong> ·
            Cliente <strong>{{ $order->full_name ?: '—' }}</strong>
            @if($order->email) · {{ $order->email }} @endif
        </div>

        <div id="invoice-provider-warning" class="alert alert-warning d-none mb-3">
            Provider e-invoicing non configurato: le fatture verranno salvate come <strong>bozza</strong>.
        </div>

        {{-- Sezione PARTNER --}}
        <section class="invoice-section" data-recipient="partner">
            <header class="invoice-section-header">
                <div>
                    <h3 class="invoice-section-title">Fattura al partner</h3>
                    <div class="invoice-recipient-name">—</div>
                </div>
                <label class="invoice-emit-toggle">
                    <input type="checkbox" class="invoice-recipient-toggle form-check-input" value="partner" id="invoice-emit-partner" checked>
                    <span>Emetti</span>
                </label>
            </header>

            <div class="invoice-flags">
                <label class="invoice-flag-item">
                    <input type="checkbox" class="invoice-flag form-check-input" data-flag="miticko_commission">
                    <span>Commissione Miticko</span>
                </label>
                <label class="invoice-flag-item">
                    <input type="checkbox" class="invoice-flag form-check-input" data-flag="bank_commission">
                    <span>Commissioni bancarie</span>
                </label>
            </div>

            <table class="invoice-lines">
                <thead>
                    <tr>
                        <th>Voce</th>
                        <th class="text-end">Qtà</th>
                        <th class="text-end">Unitario</th>
                        <th class="text-end">Totale</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Totale fattura partner</td>
                        <td class="text-end invoice-total">€ 0,00</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        {{-- Sezione CLIENTE --}}
        <section class="invoice-section" data-recipient="customer">
            <header class="invoice-section-header">
                <div>
                    <h3 class="invoice-section-title">Fattura al cliente finale</h3>
                    <div class="invoice-recipient-name">—</div>
                </div>
                <label class="invoice-emit-toggle">
                    <input type="checkbox" class="invoice-recipient-toggle form-check-input" value="customer" id="invoice-emit-customer">
                    <span>Emetti</span>
                </label>
            </header>

            <div class="invoice-flags">
                <label class="invoice-flag-item">
                    <input type="checkbox" class="invoice-flag form-check-input" data-flag="presale">
                    <span>Prevendita</span>
                </label>
            </div>

            <table class="invoice-lines">
                <thead>
                    <tr>
                        <th>Voce</th>
                        <th class="text-end">Qtà</th>
                        <th class="text-end">Unitario</th>
                        <th class="text-end">Totale</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Totale fattura cliente</td>
                        <td class="text-end invoice-total">€ 0,00</td>
                    </tr>
                </tfoot>
            </table>
        </section>
    </div>
</x-modal>
