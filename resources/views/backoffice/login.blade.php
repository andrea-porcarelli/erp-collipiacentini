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
                        <x-card class="max-w-sm mx-auto">
                            <form class="form-login">
                                <div class="row login-card">
                                    <div class="col-12 text-center mb-3">
                                        Accedi a Miticko
                                    </div>
                                    <div class="col-12">
                                        <div class="custom-input-container">
                                            <input
                                                type="email"
                                                class="form-control custom-input"
                                                id="email"
                                                placeholder="Email"
                                                autocomplete="email"
                                                name="email"
                                            >
                                            <i class="fa-regular fa-envelope custom-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-spacing-l">
                                        <div class="custom-input-container">
                                            <input
                                                type="password"
                                                class="form-control custom-input"
                                                id="password"
                                                placeholder="Password"
                                                autocomplete="password"
                                                name="password"
                                            >
                                            <i class="fa-solid fa-lock custom-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-spacing-l">
                                        <button type="button" class="btn btn-primary w-100 btn-login">Login</button>
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
