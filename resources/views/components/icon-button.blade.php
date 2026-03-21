@props([
    'icon' => null,
    'class' => null,
    'status' => 'Primary',
    'emphasis' => 'Medium',
    'size' => 'Medium',
])
<div
    data-mode="iconButtonSize-{{ $size }}"
    class="icon-button-miticko {{ $class }}"
>
    @isset($icon)
        <i class="fa-regular {{ $icon }} icon"></i>
    @endisset
</div>
