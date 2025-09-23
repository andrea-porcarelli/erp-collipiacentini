@props([
    'is_active' => false,
    'icon' => '',
    'label' => '',
    'route' => '',
])
<div class="nav-item @if($is_active) active @endif">
    <a href="@if(filled($route)){{ route($route) }}@endif">
        <span class="{{ $icon }}"></span>
        {{ $label }}
    </a>
</div>
