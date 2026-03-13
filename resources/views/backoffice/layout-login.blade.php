<!DOCTYPE html>
<html lang="en">
    @include('backoffice.components.header')
    <body data-mode="Miticko Light Desktop Medium White">
        @yield('main-content')
        @include('backoffice.components.footer')
        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        @livewireScripts
    </body>
</html>
