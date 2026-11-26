<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Widget test — {{ $product->label }}</title>
    <style>
        body { margin: 0; padding: 20px; background: #fafafa; font-family: system-ui, sans-serif; }
        .test-page { max-width: 720px; margin: 0 auto; }
        .test-page h1 { font-size: 20px; color: #555; margin-bottom: 20px; }
        .host-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    </style>
</head>
<body>
<div class="test-page">
    <h1>Widget test — {{ $product->label }} (ID {{ $product->id }})</h1>
    <div class="host-card">
        <div data-miticko-widget data-miticko-product-id="ERP-PROD-{{ $product->id }}"></div>
    </div>
</div>

<script src="{{ asset('widget.js') }}?v={{ time() }}"></script>
</body>
</html>
