<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="Light mode Default Desktop (L)">
        @yield('main-content')
        @include('backoffice.components.footer')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @livewireScripts
    </body>
</html>
