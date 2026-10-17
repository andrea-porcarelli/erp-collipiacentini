@props([
    'label' => '',
    'icon' => '',
    'is_open' => false,
])
<div {{ $attributes->class(['item', 'nav-group', 'is-open' => $is_open]) }}>
    <button type="button" class="nav-item nav-group-toggle" aria-expanded="{{ $is_open ? 'true' : 'false' }}">
        <span class="{{ $icon }}"></span>
        <span class="nav-group-label">{{ $label }}</span>
        <span class="fa fa-chevron-down nav-group-chevron"></span>
    </button>
    <div class="nav-group-children">
        {{ $slot }}
    </div>
</div>
