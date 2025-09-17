@extends('backoffice.layout', ['title' => 'Login'])

@section('main-content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-center align-items-center min-vh-100">
                    <div class="d-flex flex-column">
                        <div class="logo text-center mb-5">
                            <img src="{{ asset('assets/images/logo-miticko.png') }}">
                        </div>
                        <livewire:card blade="backoffice.components.login" title="Accedi a Miticko" title_center="true" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
