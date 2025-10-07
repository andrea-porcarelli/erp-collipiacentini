@props([
    'status' => null,
    'icon' => null
])
<div class="label-miticko {{ $status }}">
    @isset($icon)
        <span class="fa-regular fa-{{ $icon }}"></span>
    @endisset
    {{ $slot }}
</div>
