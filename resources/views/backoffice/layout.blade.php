<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="light mode default desktop (l)">
        @impersonating
            <div class="impersonate-bar" style="background: #fff3cd; border-bottom: 1px solid #ffc107; padding: 8px 24px; display: flex; align-items: center; justify-content: space-between; z-index: 9999; position: relative;">
                <span><i class="fa fa-user-secret"></i> Stai impersonando <strong>{{ Auth::user()->name }}</strong> ({{ ucfirst(Auth::user()->role) }})</span>
                <a href="{{ route('impersonate.leave') }}">
                    <x-button label="Torna al tuo account" size="small" leading="fa-arrow-left" emphasis="light" status="error" />
                </a>
            </div>
        @endImpersonating
        <div class="d-flex">
            <x-sidebar :active="$active ?? null" />
            <main class="main-content">
                @yield('main-content')
            </main>
        </div>
        @include('backoffice.components.footer')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @livewireScripts
    </body>
</html>
