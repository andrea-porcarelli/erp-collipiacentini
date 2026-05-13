<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="Miticko Light Desktop White">
        @impersonating
            <div class="impersonate-bar" style="background: #fff3cd; border-bottom: 1px solid #ffc107; padding: 8px 24px; display: flex; align-items: center; justify-content: space-between; z-index: 9999; position: relative;">
                <span><i class="fa fa-user-secret"></i> Stai impersonando <strong>{{ Auth::user()->name }}</strong> ({{ ucfirst(Auth::user()->role) }})</span>
                <a href="{{ route('impersonate.leave') }}">
                    <x-button label="Torna al tuo account" size="small" leading="fa-arrow-left" emphasis="light" status="error" />
                </a>
            </div>
        @endImpersonating
        <div class="mobile-topbar d-lg-none">
            <button type="button" class="mobile-topbar-toggle" id="mobile-sidebar-toggle" aria-label="Apri menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <img src="{{ asset('assets/images/logo-negativo.png') }}" class="mobile-topbar-logo" alt="logo">
        </div>
        <div class="d-flex">
            <x-sidebar :active="$active ?? null" />
            <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
            <main class="main-content">
                @yield('main-content')
            </main>
        </div>
        <script>
            (function () {
                var toggle = document.getElementById('mobile-sidebar-toggle');
                var backdrop = document.getElementById('sidebar-backdrop');
                function close() { document.body.classList.remove('sidebar-open'); }
                if (toggle) toggle.addEventListener('click', function () {
                    document.body.classList.toggle('sidebar-open');
                });
                if (backdrop) backdrop.addEventListener('click', close);
                document.querySelectorAll('.sidebar .nav-item').forEach(function (el) {
                    el.addEventListener('click', close);
                });
            })();
        </script>
        @include('backoffice.components.footer')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @livewireScripts
    </body>
</html>
