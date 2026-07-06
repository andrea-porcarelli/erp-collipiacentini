<!DOCTYPE html>
<html lang="it" data-mode="light mode desktop (l) compact white primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('head')
        @yield('head')
    @else
        <title>{{ __('whitelabel.page_title') }}</title>
    @endif
    <link href="{{ asset('backoffice/css/Miticko.css') }}" rel="stylesheet">
    <link href="{{ asset('backoffice/css/helpers.css') }}" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/0708d92bf1.js" crossorigin="anonymous"></script>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="{{ asset('whitelabel/whitelabel.css') }}" rel="stylesheet">
    <link href="{{ asset('whitelabel/responsive.css') }}" rel="stylesheet">
    <!-- Stripe JS -->
    <script src="https://js.stripe.com/v3/"></script>
    @livewireStyles
    @if(isset($partner) && $partner->domain_name == 'prenota.veleiaromana.it')
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-YQREBGDQWZ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-YQREBGDQWZ');
        </script>
        <script src="https://t.contentsquare.net/uxa/7ae0090746cfd.js" defer></script>
    @endif
</head>

<body data-mode="{{ ($partner ?? null)?->css_style ?: 'Miticko' }} Light Desktop White">
    <x-whitelabel.header />
    @yield('content')
    <x-whitelabel.footer />
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    @stack('scripts')
    @livewireScripts
</body>
</html>
