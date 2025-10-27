@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Aziende" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Aziende" sub_title="Le aziende">
                    <x-table-header>
                        <span class="table-header-total" > - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th style="width: 10%">#codice</th>
                                <th>Azienda</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Partita IVA</th>
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
                        {data: 'company_code'},
                        {data: 'company_name'},
                        {data: 'phone'},
                        {data: 'email'},
                        {data: 'vat_number'},
                        {data: 'options', class: 'text-end'},
                    ],
                    path: '{{ route($path.'.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} aziend${info.recordsDisplay === 1 ? 'a' : 'e'}`);
                    }
                }])
            })
        })
    </script>
@endsection
