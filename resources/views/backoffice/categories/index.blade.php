@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => 'categories'])

@section('main-content')
    <x-header-page title="Categorie prodotti" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Categorie prodotti" sub_title="Gestisci le categorie dei prodotti">
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Partner" type="daterange" name="dates" />
                        </div>
                        <span class="table-header-total" > - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th style="width: 10%">#codice</th>
                                <th>Partner</th>
                                <th>Categoria</th>
                                <th class="text-center">Prodotti</th>
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
                        {data: 'category_code'},
                        {data: 'partner'},
                        {data: 'category'},
                        {data: 'products', class: 'text-center'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route('categories.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} categori${info.recordsDisplay === 1 ? 'a' : 'e'}`);
                    }
                }])
            })
        })
    </script>
@endsection
