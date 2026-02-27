    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ Utils::site_title() }} | {{ $title ?? '' }} </title><!-- Font Awesome -->
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/miticko_old.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/custom.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/device.css') }}">
        <script src="https://kit.fontawesome.com/0708d92bf1.js" crossorigin="anonymous"></script>

        @livewireStyles
        @yield('custom-css')
    </head>
