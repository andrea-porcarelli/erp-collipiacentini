@props([
    'label' => '',
    'value' => '',
    'brand' => null,
])

@aware(['brand' => null])

@php
    $defaultBrand = config('design.default_brand', 'miticko');
    $brand = $brand ?: $defaultBrand;
    $t = config("design.brands.{$brand}.tokens") ?? config("design.brands.{$defaultBrand}.tokens", []);
@endphp

<td width="50%" valign="top" style="padding:12px 12px 12px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="font-size:11px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; color:{{ $t['text-secondary'] }}; padding-bottom:4px;">
        {{ $label }}
    </div>
    <div style="font-size:16px; font-weight:700; color:{{ $t['text-main'] }}; line-height:1.4;">
        {{ $value !== '' ? $value : $slot }}
    </div>
</td>
