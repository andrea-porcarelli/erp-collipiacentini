@extends('backoffice.layout', ['title' => 'Modifica utente', 'active' => $path])

@php
    $currentUser     = Auth::user();
    $canEditAdvanced = $currentUser->role === 'god'
        || ($currentUser->role === 'admin' && !is_null($currentUser->partner_id));
    $isGod = $currentUser->role === 'god';
@endphp

@section('main-content')
    <div class="d-flex justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button class="btn-success" emphasis="outlined" leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Utenti', 'users.index']" :second="[$model->name]" />
                <x-header-page :title="$model->name" />
            </div>
        </div>
    </div>
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Informazioni utente" sub_title="Dati principali dell'utente" class="position-relative">
                    <form id="form-user-info">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="name" label="Nome" required />
                            </div>
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="email" label="Email" required />
                            </div>
                        </div>
                    </form>
                    <div class="button-card-absolute">
                        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                    </div>
                </x-card>

                @if($canEditAdvanced)
                    <x-card title="Partner e ruolo" class="mt-4 position-relative">
                        <form id="form-user-partner-role">
                            <div class="row">
                                @if($isGod)
                                    <div class="col-12 col-sm-6">
                                        <x-select name="partner_id" :options="$partners" :model="$model" label="Partner" />
                                    </div>
                                @endif
                                <div class="col-12 col-sm-6">
                                    <x-select name="role" :options="$roles" :model="$model" label="Ruolo" required />
                                </div>
                            </div>
                        </form>
                        <div class="button-card-absolute">
                            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                        </div>
                    </x-card>

                    <x-card title="Modifica password" sub_title="Imposta una nuova password per questo utente" class="mt-4 position-relative">
                        <form id="form-user-password">
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <x-input type="password" name="password" label="Nuova password" required />
                                </div>
                                <div class="col-12 col-sm-6">
                                    <x-input type="password" name="password_confirmation" label="Conferma password" required />
                                </div>
                            </div>
                        </form>
                        <div class="button-card-absolute">
                            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('custom-script')
    <script>
        window.USER_ID = {{ $model->id }};
    </script>
    <script src="{{ asset('backoffice/js/users.js') }}?v=1.0" type="module"></script>
@endsection
