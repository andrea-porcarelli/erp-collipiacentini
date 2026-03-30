@props([
    'appearance' => null,
    'icon' => null
])
<div class="label-miticko" data-mode="labelAppearance-{{ $appearance }}">
    @isset($icon)
        <span class="fa-regular fa-{{ $icon }}"></span>
    @endisset
    {{ $slot }}
</div>
