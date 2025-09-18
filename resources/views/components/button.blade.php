@props([
    'leading' => null,
    'leading_style' => 'solid',
    'trailing' => null,
    'trailing_style' => 'solid',
    'style' => 'brand',
    'emphasis' => 'default',
    'size' => 'medium',
    'label' => '',
    'class' => '',
    'type' => 'button',
    ])
<button type="{{ $type }}" class="bt-miticko bt-m-{{ $style }} bt-m-{{ $emphasis }} bt-m-{{ $size }} {{ $class }}">
    @isset($leading)
        <i class="fa-{{ $leading_style }} {{ $leading }} icon"></i>
    @endisset
    {{ $label }}
    @isset($trailing)
        <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
    @endisset
</button>
