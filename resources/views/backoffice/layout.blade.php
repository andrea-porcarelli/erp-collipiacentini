<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="light mode desktop (l)">
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
