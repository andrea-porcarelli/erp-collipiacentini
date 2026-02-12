@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Partner" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Partner" class="position-relative" sub_title="I nostri Partner">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi partner" status="primary" emphasis="light" class="btn-create-partner" size="small" leading="fa-plus" />
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
                                <th>Azienda</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
    <x-modal title="Aggiungi nuovo partner" primary="Crea partner" secondary="annulla" width="650px" id="create-partner">
        <div class="row">
            <form id="create-partner-form" class="w-100">
                <div class="col-12">
                    <x-select name="company_id" label="Azienda" placeholder="Seleziona l'azienda" required :options="$companies" />
                    <x-input name="partner_name" label="Nome partner" placeholder="Inserisci nome partner" required />
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
                        {data: 'partner_code', type: 'string'},
                        {data: 'partner_name'},
                        {data: 'company'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} partner`);
                    }
                }])
            })

            $(document).on('click', '.btn-create-partner', function () {
                const modal = $(`#create-partner`);
                modal.modal('show');
            });

            $(document).on('click', '#create-partner .btn-cancel', function () {
                $(`#create-partner-form`).find('input').val('');
                $(`#create-partner-form`).find('select').val('');
                $(`#create-partner`).modal('hide');
            });

            $(document).on('click', '#create-partner .btn-success', function () {
                $(document).trigger('fetch', [{
                    path: `/backoffice/partners/create`,
                    method: "post",
                    data: {
                        partner_name: $(`#create-partner input[name='partner_name']`).val(),
                        company_id: $(`#create-partner select[name='company_id']`).val(),
                    },
                    then: (response) => {
                        setTimeout(() => {
                           location.href = response.redirect
                        }, 1500)
                        toastr.success('Partner creato con successo');
                    },
                    catch: (response) => {
                        $(`#create-partner input[name='partner_name']`)
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
