@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => 'categories'])

@section('main-content')
    <x-header-page title="Categorie prodotti" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Categorie prodotti" class="position-relative" brelative="true" sub_title="Gestisci le categorie dei prodotti">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi categoria" status="Primary"  class="btn-create-category" leading="fa-plus" />
                    </div>
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
    <x-modal title="Aggiungi nuova categoria" primary="Crea categoria" secondary="annulla" width="650px" id="create-category">
        <div class="row">
            <form id="create-category-form" class="w-100">
                <div class="col-12">
                    <x-input name="label" label="Nome categoria" placeholder="Inserisci il nome della categoria" required />
                    <x-input name="category_code" label="Codice categoria" placeholder="Inserisci il codice categoria" required />
                    <x-input name="iva" type="number" label="IVA (%)" placeholder="Inserisci l'aliquota IVA" />
                </div>
            </form>
        </div>
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

            $(document).on('click', '.btn-create-category', function () {
                $(`#create-category`).modal('show');
            });

            $(document).on('click', '#create-category .btn-cancel', function () {
                $(`#create-category-form`).find('input').val('');
                $(`#create-category-form`).find('select').val('');
                $(`#create-category`).modal('hide');
            });

            $(document).on('click', '#create-category .btn-success', function () {
                const data = {
                    label:         $(`#create-category input[name='label']`).val(),
                    category_code: $(`#create-category input[name='category_code']`).val(),
                    iva:           $(`#create-category input[name='iva']`).val(),
                };

                $(document).trigger('fetch', [{
                    path: `/categories/create`,
                    method: "post",
                    data: data,
                    then: (response) => {
                        setTimeout(() => {
                            location.href = response.redirect;
                        }, 1500)
                        toastr.success('Categoria creata con successo');
                    },
                    catch: (response) => {
                        $(`#create-category-form`)
                            .find(".supporting-text")
                            .first()
                            .addClass("danger")
                            .show()
                            .html(response.responseJSON.message);
                    },
                }])
            });

        })
    </script>
@endsection
