@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => 'orders'])

@section('main-content')
    <x-header-page title="Ordini" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista ordini" sub_title="visualizza gli ordini che hai ricevuto">
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Data" type="daterange" name="dates" />
                            <x-filter label="Tipo di acquisto" name="types" type="status" />
                            <x-filter label="Stato" name="status" />
                        </div>
                        <span class="table-header-total">12 ordini</span>
                        <span class="table-options">Esporta</span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th>#ordine</th>
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
                        {data: 'order_number'},
                        {data: 'customer'},
                        {data: 'created_at'},
                        {data: 'timing'},
                        {data: 'details'},
                        {data: 'type'},
                        {data: 'status'},
                        {data: 'options', class: 'text-end'},
                    ],
                    path: '{{ route('orders.data') }}',
                }])
            })
        })
    </script>
@endsection
