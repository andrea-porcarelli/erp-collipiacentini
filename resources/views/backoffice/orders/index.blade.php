@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Ordini" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista ordini" sub_title="visualizza gli ordini che hai ricevuto" brelative="true">
                    <div class="position-absolute d-flex gap-2" style="top: -70px; right: 0">
                        <x-button label="Esporta" status="Neutral" emphasis="Low" leading="fa-file-export"
                                  id="btn-export-orders" />
                        <x-button label="Registra ordine" status="Primary" leading="fa-plus"
                                  id="btn-open-register-order"
                                  :dataset="['bs-toggle' => 'modal', 'bs-target' => '#modal-register-order']" />
                    </div>
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Data" type="daterange" name="dates" />
                            <x-filter label="Codice prenotazione" type="text" name="order_number" />
                            <x-filter label="Prodotto" type="text" name="label" />
                            <x-filter label="Cliente" type="text" name="customer" />
                            <x-filter label="Tipo di acquisto" name="types" type="status" />
                            <x-filter label="Stato" name="status" />
                        </div>
                        <span class="table-header-total"> - </span>
                        <span class="table-options">Esporta</span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th style="width: 10%">#ordine</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Acquisto</th>
                                <th>Tipologia</th>
                                <th>Stato</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
    <x-modal id="filter-text" title="Filtra per prodotto" primary="Salva" secondary="annulla" width="400px">
        <x-input name="filter_text_value" label="Nome prodotto" placeholder="Cerca per nome" />
    </x-modal>
    <x-modal id="filter-daterange" title="Seleziona periodo" primary="Salva" secondary="annulla" width="350px">
        <div class="d-flex align-items-center justify-content-center">
            <div id="calendar-container" data-filter="dates"></div>
        </div>
    </x-modal>
    <x-modal id="filter-status" title="Filtra stato dell'ordine" primary="Salva" secondary="annulla" width="350px">
        <ul class="order-statuses">
        @foreach($statuses as $status => $label)
            <li>
                <x-checkbox :label="$label" :name="$status" />
            </li>
        @endforeach
        </ul>
    </x-modal>
    <x-modal id="order-detail" title="Riepilogo ordine">
        <div id="order-detail-body"></div>
    </x-modal>

    {{-- MODAL "Registra ordine" ---------------------------------------------------- --}}
    @include('backoffice.orders._modal-register-order')
@endsection

@section('custom-script')
    <script src='https://cdn.jsdelivr.net/momentjs/latest/moment.min.js'></script>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script>
        $(document).ready(function(){

            setTimeout(() => {
                $(document).trigger('datatable', [{
                    columns: [
                        {data: 'order_number', width: '200px'},
                        {data: 'customer'},
                        {data: 'created_at'},
                        {data: 'timing'},
                        {data: 'details'},
                        {data: 'type'},
                        {data: 'status'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} ordin${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                    }
                }])
            })

            $('#btn-export-orders').on('click', function () {
                const $btn = $(this);
                if ($btn.prop('disabled')) return;
                const filters = {};
                $('.filters-miticko input, .filters-miticko select').each(function () {
                    const name = $(this).attr('name');
                    const value = $(this).val();
                    if (name && value !== null && value !== undefined && value !== '') {
                        filters[name] = value;
                    }
                });
                $btn.prop('disabled', true);
                const originalHtml = $btn.html();
                $btn.html('<i class="fa-regular fa-spinner fa-spin icon"></i> Esportando...');
                const url = @json(route('orders.export')) + '?' + $.param({ filters });
                // Uso di fetch per poter intercettare errori JSON prima del download.
                fetch(url, { credentials: 'same-origin' })
                    .then(async (res) => {
                        const contentType = res.headers.get('content-type') || '';
                        if (!res.ok || contentType.includes('application/json')) {
                            const body = await res.json().catch(() => ({}));
                            const msg = body?.response || body?.message || 'Errore durante l\'export';
                            toastr.error(msg);
                            return;
                        }
                        const blob = await res.blob();
                        const disposition = res.headers.get('content-disposition') || '';
                        let filename = 'miticko-ordini.xlsx';
                        const m = disposition.match(/filename\s*=\s*"?([^\";]+)"?/i);
                        if (m) filename = m[1];
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        setTimeout(() => {
                            URL.revokeObjectURL(link.href);
                            link.remove();
                        }, 100);
                    })
                    .catch(() => toastr.error('Errore durante l\'export'))
                    .finally(() => {
                        $btn.prop('disabled', false).html(originalHtml);
                    });
            });

            $(document).on('click', '.btn-preview-order', function() {
                const orderId = $(this).data('order-id');
                $.ajax({
                    url: `/orders/${orderId}/preview`,
                    method: 'GET',
                    dataType: 'json',
                }).done(function(res) {
                    $('#order-detail-body').html(res.response);
                    $('#order-detail').modal('show');
                }).fail(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Errore nel caricamento del riepilogo ordine');
                    }
                });
            });
        })
    </script>

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
