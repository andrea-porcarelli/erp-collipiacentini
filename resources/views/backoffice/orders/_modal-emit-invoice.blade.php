<x-modal id="modal-emit-invoice" title="Emetti fattura" primary="Emetti fatture selezionate" secondary="Annulla" width="720px">
    <div id="invoice-loading" class="text-center py-4">
        <i class="fa-regular fa-spinner fa-spin"></i>
        <div class="small text-secondary mt-2">Calcolo in corso...</div>
    </div>

    <div id="invoice-content" class="d-none">
        <div class="mb-3 small text-secondary">
            Ordine <strong>#{{ $order->order_number }}</strong> ·
            Cliente <strong>{{ $order->full_name ?: '—' }}</strong>
            @if($order->email) · {{ $order->email }} @endif
        </div>

        <div id="invoice-provider-warning" class="alert alert-warning d-none mb-3 small">
            Provider e-invoicing non configurato: le fatture verranno salvate come <strong>bozza</strong>.
        </div>

        {{-- Sezione PARTNER --}}
        <div class="card-miticko mb-3" data-recipient="partner">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <div class="fw-bold">Fattura al partner</div>
                    <div class="small text-secondary invoice-recipient-name">—</div>
                </div>
                <div class="form-check">
                    <input class="form-check-input invoice-recipient-toggle" type="checkbox" value="partner" id="invoice-emit-partner" checked>
                    <label class="form-check-label small" for="invoice-emit-partner">Emetti</label>
                </div>
            </div>
            <div class="row g-2 invoice-flags mb-2">
                <div class="col-6 col-md-4">
                    <label class="small d-flex align-items-center gap-2">
                        <input type="checkbox" class="invoice-flag" data-flag="miticko_commission">
                        Commissione Miticko
                    </label>
                </div>
                <div class="col-6 col-md-4">
                    <label class="small d-flex align-items-center gap-2">
                        <input type="checkbox" class="invoice-flag" data-flag="bank_commission">
                        Commissioni bancarie
                    </label>
                </div>
            </div>
            <table class="table table-sm mb-0 invoice-lines">
                <thead>
                    <tr>
                        <th>Voce</th>
                        <th class="text-end" style="width:80px">Qtà</th>
                        <th class="text-end" style="width:120px">Unitario</th>
                        <th class="text-end" style="width:120px">Totale</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Totale fattura partner</td>
                        <td class="text-end fw-bold invoice-total">€ 0,00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Sezione CLIENTE --}}
        <div class="card-miticko" data-recipient="customer">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <div class="fw-bold">Fattura al cliente finale</div>
                    <div class="small text-secondary invoice-recipient-name">—</div>
                </div>
                <div class="form-check">
                    <input class="form-check-input invoice-recipient-toggle" type="checkbox" value="customer" id="invoice-emit-customer">
                    <label class="form-check-label small" for="invoice-emit-customer">Emetti</label>
                </div>
            </div>
            <div class="row g-2 invoice-flags mb-2">
                <div class="col-6 col-md-4">
                    <label class="small d-flex align-items-center gap-2">
                        <input type="checkbox" class="invoice-flag" data-flag="presale">
                        Prevendita
                    </label>
                </div>
            </div>
            <table class="table table-sm mb-0 invoice-lines">
                <thead>
                    <tr>
                        <th>Voce</th>
                        <th class="text-end" style="width:80px">Qtà</th>
                        <th class="text-end" style="width:120px">Unitario</th>
                        <th class="text-end" style="width:120px">Totale</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Totale fattura cliente</td>
                        <td class="text-end fw-bold invoice-total">€ 0,00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-modal>
