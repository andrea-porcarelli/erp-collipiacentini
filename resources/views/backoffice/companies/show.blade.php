@extends('backoffice.layout', ['title' => 'Modifica azienda', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="outlined"  leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Aziende', 'companies.index']" :second="[$model->company_name]" />
                <x-header-page :title="$model->company_name" />
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="primary" label="Salva modifiche" leading="fa-save" />
            </div>
        </div>
    </div>
    <div class="w-100">
        <ul class="nav nav-tabs entity-tabs" id="companyTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <x-button
                    class="active"
                    status="secondary"
                    id="info-tab"
                    role="tab"
                    label="Informazioni"
                    :dataset="['bs-target' => '#info-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'info-panel', 'selected' => 'true']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="token-tab"
                    role="tab"
                    label="Token"
                    :dataset="['bs-target' => '#token-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'token-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="users-tab"
                    role="tab"
                    label="Utenti"
                    :dataset="['bs-target' => '#users-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'users-panel', 'selected' => 'false']"
                />
            </li>
        </ul>

        <div class="tab-content" id="companyTabsContent">
            {{-- Tab 1: Informazioni --}}
            <div class="tab-pane fade show active" id="info-panel" role="tabpanel" aria-labelledby="info-tab">
                <form id="update-company-form">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Informazioni azienda" sub_title="Dati principali dell'azienda">
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="company_name" label="Nome azienda" required />
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="company_code" label="Codice azienda" required />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="vat_number" label="Partita IVA" required />
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="phone" label="Telefono" />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="email" label="Email" />
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="email_notify" label="Email notifiche" />
                                    </div>
                                </div>
                            </x-card>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tab 2: Token --}}
            <div class="tab-pane fade" id="token-panel" role="tabpanel" aria-labelledby="token-tab">
                <div class="row">
                    <div class="col-12">
                        <x-card title="Token di accesso" sub_title="Il token viene utilizzato per autenticare le richieste dallo shop">
                            <label>Token</label>
                            <div class="d-flex align-items-center gap-3 mt-2">
                                <div class="flex-grow-1">
                                    <x-input name="token" :value="$model->token" disabled />
                                </div>
                                <div>
                                    <x-button label="Genera nuovo token" status="primary" emphasis="outlined" size="small" leading="fa-rotate" class="btn-generate-token" />
                                </div>
                            </div>
                            @if($model->token)
                                <x-supporting-text icon="fa-regular fa-circle-info" message="Attenzione: generare un nuovo token invaliderà quello attuale" />
                            @else
                                <x-supporting-text icon="fa-regular fa-circle-info" message="Nessun token generato. Clicca il pulsante per crearne uno." />
                            @endif
                        </x-card>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Utenti --}}
            <div class="tab-pane fade" id="users-panel" role="tabpanel" aria-labelledby="users-tab">
                <div class="row">
                    <div class="col-12">
                        <x-card title="Utenti associati" sub_title="Gli utenti associati a questa azienda">
                            <div class="table-responsive">
                                <table class="table table-miticko">
                                    <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Ruolo</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($model->users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ ucfirst($user->role) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('users.show', $user->id) }}">
                                                    <x-button label="Modifica" size="small" leading="fa-edit" emphasis="outlined"/>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">Nessun utente associato</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom-css')
<style>
    .entity-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 24px;
        gap: 8px;
    }

    .entity-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }

    .entity-tabs .nav-link:hover {
        border-bottom-color: #dee2e6;
        color: #495057;
    }

    .entity-tabs .nav-link.active {
        border-bottom-color: var(--bs-primary, #0d6efd);
        color: var(--bs-primary, #0d6efd);
        background-color: transparent;
    }

    .entity-tabs .nav-link i {
        font-size: 14px;
    }

    .tab-content {
        padding-top: 8px;
    }
</style>
@endsection

@section('custom-script')
    <script>
        $(document).ready(function(){
            $(document).on('click', '.btn-generate-token', function () {
                $(document).trigger('sweetConfirmTrigger', [{
                    text: 'Confermi la generazione di un nuovo token?',
                    title: 'Il token attuale verrà sostituito',
                    callback: () => {
                        $(document).trigger('fetch', [{
                            path: `/backoffice/companies/{{ $model->id }}/generate-token`,
                            method: "post",
                            then: (response) => {
                                $(`input[name='token']`).val(response.token);
                                toastr.success('Token generato con successo');
                            },
                            catch: () => {
                                toastr.error('Errore durante la generazione del token');
                            },
                        }]);
                    }
                }])
            });
        })
    </script>
@endsection
