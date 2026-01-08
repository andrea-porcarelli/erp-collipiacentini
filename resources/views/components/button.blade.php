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
    'disabled' => null,
    'dataset' => [],
    'href' => null
])

@if($href)
    <a
        href="{{ $disabled ? '#' : $href }}"
        data-mode="{{ $size }} {{ $status }}"
        class="bt-miticko {{ $class }} bt-m-{{ $emphasis }} {{ $disabled ? 'disabled' : '' }}"
        @if($disabled) aria-disabled="true" onclick="return false;" @endif
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
    </a>
@else
    <button
        data-mode="{{ $size }} {{ $status }}"
        type="{{ $type }}"
        class="bt-miticko {{ $class }} bt-m-{{ $emphasis }}"
        @if($disabled) disabled @endif
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
@endif
