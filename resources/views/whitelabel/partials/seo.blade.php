@php
    $meta   = $seo['meta']   ?? [];
    $jsonLd = $seo['jsonLd'] ?? null;
    $og     = $meta['og']      ?? [];
    $tw     = $meta['twitter'] ?? [];
@endphp

<title>{{ $meta['title'] ?? __('whitelabel.page_title') }}</title>

@if(!empty($meta['description']))
    <meta name="description" content="{{ $meta['description'] }}">
@endif

@if(!empty($meta['keywords']))
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif

@if(!empty($meta['canonical']))
    <link rel="canonical" href="{{ $meta['canonical'] }}">
@endif

@foreach($og as $prop => $value)
    @if(!empty($value))
        <meta property="og:{{ $prop }}" content="{{ $value }}">
    @endif
@endforeach

@foreach($tw as $prop => $value)
    @if(!empty($value))
        <meta name="twitter:{{ $prop }}" content="{{ $value }}">
    @endif
@endforeach

@if(!empty($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
