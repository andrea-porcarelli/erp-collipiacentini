@props([
    'is_active' => false,
    'icon' => '',
    'label' => '',
    'route' => '',
])
<div class="item">
    <a href="@if(filled($route)){{ route($route) }}@endif" class="nav-item @if($is_active) active @endif">
        <span class="{{ $icon }}"></span>
        {{ $label }}
    </a>
</div>
