@props([
    'is_active' => false,
    'label' => '',
    'route' => '',
])
<div class="item sub-item">
    <a href="@if(filled($route)){{ route($route) }}@endif" class="nav-item nav-sub-item @if($is_active) active @endif">
        {{ $label }}
    </a>
</div>
