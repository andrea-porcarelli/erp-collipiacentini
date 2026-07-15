@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Clienti" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista clienti" class="position-relative" brelative="true" sub_title="visualizza i clienti">
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Cliente" type="text" name="customer" />
                            <x-filter label="Ha acquistato" type="status" name="purchased" />
                        </div>
                        <span class="table-header-total"> - </span>
                        <span class="table-options">Esporta</span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Contatti</th>
                                <th>Indirizzo</th>
                                <th>Acquisti</th>
                                <th>Iscritto il</th>
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
    <x-modal id="filter-text" title="Filtra per cliente" primary="Salva" secondary="annulla" width="400px">
        <x-input name="filter_text_value" label="Cognome, email o telefono" placeholder="Cerca per cognome, email o telefono" />
    </x-modal>
    <x-modal id="filter-status" title="Filtra per acquisto" primary="Salva" secondary="annulla" width="350px">
        <ul class="order-statuses">
            <li>
                <x-checkbox label="Sì" name="yes" />
            </li>
            <li>
                <x-checkbox label="No" name="no" />
            </li>
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
                        {data: 'full_name'},
                        {data: 'contacts'},
                        {data: 'address'},
                        {data: 'orders'},
                        {data: 'created_at'},
                        {data: 'options', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} client${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                    }
                }])
            })
        })
    </script>
@endsection
