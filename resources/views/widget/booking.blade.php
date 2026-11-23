<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prenotazione — {{ $product->label }}</title>
    <link rel="stylesheet" href="{{ asset('css/widget.css') }}">
</head>
<body>
    <div id="miticko-widget-root"
         data-product-id="{{ $product->id }}"
         data-product-code="{{ $product->product_code }}"
         data-partner-id="{{ $product->partner_id }}">
        {{-- TODO [A3]: widget UI (Data → Orario → Visitatori → Checkout). --}}
        <p>Widget di prenotazione in costruzione.</p>
    </div>

    <script>
        window.MITICKO_API_BASE = "{{ url('/api/v1') }}";
        window.MITICKO_PRODUCT_ID = "ERP-PROD-{{ $product->id }}";
    </script>
    {{-- TODO [A3]: @vite('resources/js/widget/index.js') dopo aver creato l'entry Vite. --}}
</body>
</html>
