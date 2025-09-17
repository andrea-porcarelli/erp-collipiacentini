<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="desktop (l)" data-color="light mode">
        @yield('main-content')
        @include('backoffice.components.footer')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @livewireScripts
    </body>
</html>
