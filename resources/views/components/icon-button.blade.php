@props([
    'icon' => null,
    'class' => null,
    'appearance' => 'Primary',
    'emphasis' => 'Medium',
    'size' => 'Medium',
])
<div
    {{ $attributes->except(['class', 'data-mode']) }}
    data-mode="iconButtonSize-{{ $size }} buttonAppearance-{{ ucfirst($appearance) }} buttonEmphasis-{{ ucfirst($emphasis) }}"
    class="icon-button-miticko {{ $class }}"
>
    @isset($icon)
        <i class="fa-regular {{ $icon }} icon"></i>
    @endisset
</div>
