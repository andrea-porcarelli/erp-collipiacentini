@extends('backoffice.layout', ['title' => 'Modifica utente', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="outlined"  leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Utenti', 'users.index']" :second="[$model->name]" />
                <x-header-page :title="$model->name" />
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="primary" label="Salva modifiche" leading="fa-save" />
            </div>
        </div>
    </div>
    <div class="w-100">
        <form id="update-user-form">
            <div class="row">
                <div class="col-12">
                    <x-card title="Informazioni utente" sub_title="Dati principali dell'utente">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="name" label="Nome" required />
                            </div>
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="email" label="Email" required />
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <x-input name="role" label="Ruolo" :value="ucfirst($model->role)" disabled />
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </form>
    </div>
@endsection
