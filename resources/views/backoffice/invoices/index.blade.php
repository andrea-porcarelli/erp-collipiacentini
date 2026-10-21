@extends('backoffice.layout', ['title' => 'Fatturazione', 'active' => $path])

@section('main-content')
    <x-header-page title="Fatturazione" />

    {{-- Banner feedback (success/error) --}}
    <div id="invoice-feedback" class="d-none">
        <div class="invoice-feedback-banner" data-type="success">
            <i class="fa-solid fa-circle-check"></i>
            <span class="invoice-feedback-text"></span>
        </div>
    </div>

    {{-- Chip-tab stato (quick filters) --}}
    <div class="d-flex gap-2 mb-spacing-xl invoice-chip-tabs">
        @foreach($chipStatuses as $value => $label)
            <x-chip
                :label="$label"
                :appearance="$value === 'all' ? 'Active' : 'Resting'"
                class="invoice-chip-tab"
                :dataset="['status' => $value]"
            />
        @endforeach
    </div>

    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Ordini" sub_title="" brelative="true">
                    <x-table-header>
                        <div class="filters-miticko">
                            {{-- Filtro `status` controllato dalle chip-tab sopra (default: all) --}}
                            <input type="hidden" name="status" id="invoice-status-filter" value="all">
                            <x-filter label="data pagamento" type="daterange" name="dates" />
                            <x-filter label="prodotto" type="text" name="products" />
                            @if(count($partners) > 1)
                                <x-filter label="partner" name="partners" type="partner" />
                            @endif
                            <x-filter label="cliente (cerca)" type="text" name="customer" />
                        </div>
                        <span class="table-header-total"> - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th>#ordine</th>
                                <th>Data acquisto</th>
                                <th>Stato pag.</th>
                                <th>Cliente</th>
                                <th>Partner</th>
                                <th>Prodotto</th>
                                <th class="text-end">Totale</th>
                                <th class="text-end">Stato fattura</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    {{-- Modali dei filtri --}}
    <x-modal id="filter-text" title="Filtra" primary="Salva" secondary="annulla" width="400px">
        <x-input name="filter_text_value" label="Cerca" placeholder="Inserisci testo" />
    </x-modal>
    <x-modal id="filter-daterange" title="Seleziona periodo" primary="Salva" secondary="annulla" width="350px">
        <div class="d-flex align-items-center justify-content-center">
            <div id="calendar-container" data-filter="dates"></div>
        </div>
    </x-modal>
    @if(count($partners) > 1)
        <x-modal id="filter-partner" title="Filtra per partner" primary="Salva" secondary="annulla" width="400px">
            <ul class="order-statuses">
                @foreach($partners as $id => $name)
                    <li><x-checkbox :label="$name" :name="(string) $id" /></li>
                @endforeach
            </ul>
        </x-modal>
    @endif

    {{-- Modale Imposta fattura (2 step) --}}
    @include('backoffice.orders._modal-emit-invoice-wizard')
    {{-- Modale Dettaglio invio (storico log provider di fatturazione) --}}
    @include('backoffice.orders._modal-invoice-logs')
@endsection

@section('custom-css')
    <style>
        .invoice-chip-tabs .chip-miticko { cursor: pointer; }
        .invoice-feedback-banner {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .invoice-feedback-banner[data-type="success"] {
            background: #1F7D4D;
            color: #fff;
        }
        .invoice-feedback-banner[data-type="error"] {
            background: #D93025;
            color: #fff;
        }
        #invoice-feedback.visible { display: block; }
        .label-InvoiceAction {
            background: #FF8A50;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .label-InvoiceAction:hover { background: #ff7b36; color: #fff; }
    </style>
@endsection

@section('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script>
        $(function () {
            // Chip-tab stato: aggiorna il valore di #invoice-status-filter (dentro .filters-miticko)
            // → DataTable lo include nel payload come filters[status] alla successiva reload.
            $(document).on('click', '.invoice-chip-tab', function () {
                $('.invoice-chip-tab').attr('data-mode', 'chipAppearance-Resting');
                $(this).attr('data-mode', 'chipAppearance-Active');
                $('#invoice-status-filter').val($(this).data('status') || 'all');
                const dt = $('.datatable').DataTable();
                dt.clear();
                dt.ajax.reload();
            });

            setTimeout(() => {
                $(document).trigger('datatable', [{
                    columns: [
                        { data: 'order_number' },
                        { data: 'paid_at' },
                        { data: 'payment_status' },
                        { data: 'customer' },
                        { data: 'partner' },
                        { data: 'product' },
                        { data: 'total_formatted', class: 'text-end' },
                        { data: 'invoice_status', class: 'text-end' },
                    ],
                    path: '{{ route('invoices.data') }}',
                    drawCallback: function (api) {
                        const info = api.api.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} ordin${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                    }
                }]);
            });
        });

        window.APP_BANNER = (type, message) => {
            const $box = $('#invoice-feedback');
            $box.find('.invoice-feedback-banner').attr('data-type', type);
            $box.find('.invoice-feedback-text').text(message);
            $box.addClass('visible').removeClass('d-none');
            if (type === 'success') {
                setTimeout(() => $box.removeClass('visible').addClass('d-none'), 4000);
            }
        };
    </script>
    <script src="{{ asset('backoffice/js/invoice-modal.js') }}?v={{ filemtime(public_path('backoffice/js/invoice-modal.js')) }}"></script>
    <script src="{{ asset('backoffice/js/invoice-logs-modal.js') }}?v={{ filemtime(public_path('backoffice/js/invoice-logs-modal.js')) }}"></script>
@endsection
