@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Prodotti" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista prodotti" class="position-relative"  sub_title="I prodotti dei tuoi Partners">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi prodotto" status="primary" emphasis="light" class="btn-create-product" size="small" leading="fa-plus" />
                    </div>
                    <x-table-header>
                        <span class="table-header-total" > - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th style="width: 10%">#codice</th>
                                <th>Partner</th>
                                <th>Categoria</th>
                                <th>Prodotto</th>
                                <th>Prezzi</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
    <x-modal title="Aggiungi nuovo prodotto" primary="Crea prodotto" secondary="annulla" width="650px" id="create-product">
        <div class="row">
            <form id="create-product-form" class="w-100">
                <div class="col-12">
                    @if(Auth::user()->role === 'god')
                        <x-select name="partner_id" label="Partner" placeholder="Seleziona il partner" required :options="$partners" />
                    @endif
                    <x-input name="label" label="Nome prodotto" placeholder="Inserisci nome prodotto" required />
                    <x-supporting-text icon="fa-regular fa-circle-info" message="Il nome inserito è per uso interno, quello visualizzato online verrà richiesto nella fase successiva" />
                </div>
            </form>
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
                        {data: 'product_code'},
                        {data: 'partner'},
                        {data: 'category'},
                        {data: 'label'},
                        {data: 'pricing'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} prodott${info.recordsDisplay === 1 ? 'o' : 'i'}`);
                    }
                }])
            })

            $(document).on('click', '.btn-create-product', function () {
                const modal = $(`#create-product`);
                modal.modal('show');
            });

            $(document).on('click', '.btn-cancel', function () {
                $(`#create-product-form`).find('input').val('');
                $(`#create-product`).modal('hide');
            });

            $(document).on('click', '#create-product .btn-success', function () {
                $(document).trigger('fetch', [{
                    path: `/backoffice/products/create`,
                    method: "post",
                    data: {
                        label: $(`#create-product input[name='label']`).val()
                    },
                    then: (response) => {
                        setTimeout(() => {
                           location.href = response.redirect
                        }, 1500)
                        toastr.success('Messaggio di successo');
                    },
                    catch: (response) => {
                            $(`#create-product input[name='label']`)
                                .parent()
                                .parent()
                            .find(".supporting-text")
                            .addClass("danger")
                            .show()
                            .html(response.responseJSON.message);
                    },
                }])
            });

        })
    </script>
@endsection
