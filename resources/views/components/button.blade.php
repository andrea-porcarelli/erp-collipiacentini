@props([
    'leading' => null,
    'leading_style' => 'regular',
    'trailing' => null,
    'trailing_style' => 'regular',
    'emphasis' => 'default',
    'status' => 'primary',
    'size' => 'medium',
    'label' => '',
    'class' => '',
    'type' => 'button',
    'dataset' => []
])
<button
    data-mode="{{ $size }} {{ $status }}"
    type="{{ $type }}"
    class="bt-miticko {{ $class }} bt-m-{{ $emphasis }}"
    @if(!empty($dataset))
        @foreach($dataset as $attribute => $value)
            data-{{ $attribute }}="{{ $value }}"
        @endforeach
    @endif
>
    @isset($leading)
        <i class="fa-{{ $leading_style }} {{ $leading }} icon"></i>
    @endisset
    {{ $label }}
    @isset($trailing)
        <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
    @endisset
</button>
