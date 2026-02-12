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
                    @if(in_array(Auth::user()->role, ['god', 'admin']))
                        <x-select name="company_id" label="Azienda" placeholder="Seleziona l'azienda" required :options="$companies" />
                        <div id="product-partner-select-container">
                            <x-select name="partner_id" label="Partner" placeholder="Prima seleziona un'azienda" required :options="[]" />
                        </div>
                    @elseif(Auth::user()->role === 'company')
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
                        var realApi = api.api;
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} prodott${info.recordsDisplay === 1 ? 'o' : 'i'}`);
                    }
                }])
            })

            $(document).on('click', '.btn-create-product', function () {
                $(`#create-product`).modal('show');
            });

            $(document).on('click', '#create-product .btn-cancel', function () {
                $(`#create-product-form`).find('input').val('');
                $(`#create-product-form`).find('select').val('');
                $(`#create-product`).modal('hide');
            });

            @if(in_array(Auth::user()->role, ['god', 'admin']))
            $(document).on('change', '#create-product select[name="company_id"]', function () {
                const companyId = $(this).val();
                const partnerSelect = $(`#create-product select[name='partner_id']`);

                partnerSelect.html('<option value="">Caricamento...</option>');

                if (!companyId) {
                    partnerSelect.html('<option value="">Prima seleziona un\'azienda</option>');
                    return;
                }

                $.get(`/backoffice/products/partners-by-company/${companyId}`, function (partners) {
                    let options = '<option value="">Scegli</option>';
                    partners.forEach(function (partner) {
                        options += `<option value="${partner.id}">${partner.label}</option>`;
                    });
                    partnerSelect.html(options);
                });
            });
            @endif

            $(document).on('click', '#create-product .btn-success', function () {
                const data = {
                    label: $(`#create-product input[name='label']`).val(),
                };

                const partnerSelect = $(`#create-product select[name='partner_id']`);
                if (partnerSelect.length) {
                    data.partner_id = partnerSelect.val();
                }

                $(document).trigger('fetch', [{
                    path: `/backoffice/products/create`,
                    method: "post",
                    data: data,
                    then: (response) => {
                        setTimeout(() => {
                           location.href = response.redirect
                        }, 1500)
                        toastr.success('Prodotto creato con successo');
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
