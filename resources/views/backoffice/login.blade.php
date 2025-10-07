@extends('backoffice.layout-login', ['title' => 'Login'])

@section('main-content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-center align-items-center min-vh-100">
                    <div class="d-flex flex-column" style="width:380px">
                        <div class="logo text-center mb-5">
                            <img src="{{ asset('assets/images/logo-miticko.png') }}" style="width: 25%">
                        </div>
                        <x-card class="max-w-sm mx-auto">
                            <form class="form-login">
                                <div class="row">
                                    <div class="col-12 text-center mb-3">
                                        Accedi a Miticko
                                    </div>
                                    <div class="col-12">
                                        <x-input leading="fa-envelope" leading_style="regular" name="email" placeholder="Email" />
                                    </div>
                                    <div class="col-12 mt-spacing-l">
                                        <x-input leading="fa-lock"  name="password" type="password" placeholder="Password" />
                                    </div>
                                    <div class="col-12 mt-spacing-l">
                                        <x-button label="Login" class="w-100 btn-login"/>
                                    </div>
                                    <div class="col-12 mt-spacing-l text-center">
                                        <a href="" class="text-small">Password dimenticata</a>
                                    </div>
                                </div>
                            </form>
                        </x-card>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
