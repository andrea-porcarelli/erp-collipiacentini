@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Aziende" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Aziende" class="position-relative" sub_title="Le aziende">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi azienda" status="primary" emphasis="light" class="btn-create-company" size="small" leading="fa-plus" />
                    </div>
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
                                <th>WhiteLabel</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
    <x-modal title="Aggiungi nuova azienda" primary="Crea azienda" secondary="annulla" width="650px" id="create-company">
        <div class="row">
            <form id="create-company-form" class="w-100">
                <div class="col-12">
                    <x-input name="company_name" label="Nome azienda" placeholder="Inserisci nome azienda" required />
                    <x-input name="vat_number" label="Partita IVA" placeholder="Inserisci partita IVA" required />
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
                        {data: 'company_code'},
                        {data: 'company_name'},
                        {data: 'phone'},
                        {data: 'email'},
                        {data: 'vat_number'},
                        {data: 'has_whitelabel'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path.'.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} aziend${info.recordsDisplay === 1 ? 'a' : 'e'}`);
                    }
                }])
            })

            $(document).on('click', '.btn-create-company', function () {
                const modal = $(`#create-company`);
                modal.modal('show');
            });

            $(document).on('click', '#create-company .btn-cancel', function () {
                $(`#create-company-form`).find('input').val('');
                $(`#create-company`).modal('hide');
            });

            $(document).on('click', '#create-company .btn-success', function () {
                $(document).trigger('fetch', [{
                    path: `/backoffice/companies/create`,
                    method: "post",
                    data: {
                        company_name: $(`#create-company input[name='company_name']`).val(),
                        vat_number: $(`#create-company input[name='vat_number']`).val(),
                    },
                    then: (response) => {
                        setTimeout(() => {
                           location.href = response.redirect
                        }, 1500)
                        toastr.success('Azienda creata con successo');
                    },
                    catch: (response) => {
                        $(`#create-company input[name='company_name']`)
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
