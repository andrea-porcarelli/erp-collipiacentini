@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Utenti CP" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista utenti" class="position-relative" sub_title="Gli utenti del pannello di controllo">
                    <div class="position-absolute" style="top: -70px; right: 0">
                        <x-button label="Aggiungi utente" status="primary" emphasis="light" class="btn-create-user" size="small" leading="fa-plus" />
                    </div>
                    <x-table-header>
                        <span class="table-header-total" > - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ruolo</th>
                                <th>Associazione</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
    <x-modal title="Aggiungi nuovo utente" primary="Crea utente" secondary="annulla" width="650px" id="create-user">
        <div class="row">
            <form id="create-user-form" class="w-100">
                <div class="col-12">
                    <x-input name="name" label="Nome" placeholder="Inserisci nome" required />
                    <x-input name="email" label="Email" placeholder="Inserisci email" required />
                    <x-input name="password" type="password" label="Password" placeholder="Inserisci password" required />
                    <x-select name="role" label="Ruolo" placeholder="Seleziona il ruolo" required :options="$roles" />
                    <div id="partner-select-container" style="display: none;">
                        <x-select name="partner_id" label="Partner" placeholder="Seleziona il partner" :options="$partners" />
                    </div>
                    <div id="company-select-container" style="display: none;">
                        <x-select name="company_id" label="Azienda" placeholder="Seleziona l'azienda" :options="$companies" />
                    </div>
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
                        {data: 'name'},
                        {data: 'email'},
                        {data: 'role'},
                        {data: 'association'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api;
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} utent${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                    }
                }])
            })

            $(document).on('click', '.btn-create-user', function () {
                $(`#create-user`).modal('show');
            });

            $(document).on('click', '#create-user .btn-cancel', function () {
                $(`#create-user-form`).find('input').val('');
                $(`#create-user-form`).find('select').val('');
                $(`#partner-select-container`).hide();
                $(`#company-select-container`).hide();
                $(`#create-user`).modal('hide');
            });

            $(document).on('change', '#create-user select[name="role"]', function () {
                const role = $(this).val();
                $(`#partner-select-container`).toggle(role === 'partner');
                $(`#company-select-container`).toggle(role === 'company');
            });

            $(document).on('click', '#create-user .btn-success', function () {
                const role = $(`#create-user select[name='role']`).val();
                const data = {
                    name: $(`#create-user input[name='name']`).val(),
                    email: $(`#create-user input[name='email']`).val(),
                    password: $(`#create-user input[name='password']`).val(),
                    role: role,
                };

                if (role === 'partner') {
                    data.partner_id = $(`#create-user select[name='partner_id']`).val();
                }
                if (role === 'company') {
                    data.company_id = $(`#create-user select[name='company_id']`).val();
                }

                $(document).trigger('fetch', [{
                    path: `/backoffice/users/create`,
                    method: "post",
                    data: data,
                    then: (response) => {
                        setTimeout(() => {
                           location.href = response.redirect
                        }, 1500)
                        toastr.success('Utente creato con successo');
                    },
                    catch: (response) => {
                        $(`#create-user-form`)
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
