    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
        <title>{{ Utils::site_title() }} | {{ $title ?? '' }} </title><!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/miticko.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/custom.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('/backoffice/css/device.css') }}">
        @livewireStyles
    </head>
