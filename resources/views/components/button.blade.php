@props([
    'leading' => null,
    'leading_style' => 'regular',
    'trailing' => null,
    'trailing_style' => 'regular',
    'status' => 'Primary',
    'emphasis' => 'Medium',
    'size' => 'Medium',
    'label' => '',
    'class' => '',
    'type' => 'button',
    'disabled' => null,
    'dataset' => [],
    'href' => null,
    'id' => null,
    'role' => null,
    'ariaset' => [],
    'w_click' => null,
    'style' => null
])

@if($href)
    <a
        href="{{ $disabled ? '#' : $href }}"
        data-mode="buttonSize-{{ $size }} buttonEmphasis-{{ $emphasis }} buttonAppearance-{{ $status }}"
        class="bt-miticko {{ $class }} {{ $disabled ? 'disabled' : '' }}"
        @if($disabled) aria-disabled="true" onclick="return false;" @endif
        @isset($id) id="{{ $id }}" @endisset
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
        data-mode="buttonSize-{{ $size }} buttonEmphasis-{{ $emphasis }}  buttonAppearance-{{ $status }}"
        type="{{ $type }}"
        class="bt-miticko {{ $class }} btn-miticko-{{ $emphasis }}"
        @isset($id) id="{{ $id }}" @endisset
        @isset($style) style="{{ $style }}" @endisset
        @isset($role) role="{{ $role }}" @endisset
        @if($disabled) disabled @endif
        @if(!empty($dataset))
            @foreach($dataset as $attribute => $value)
                data-{{ $attribute }}="{{ $value }}"
            @endforeach
        @endif
        @if(!empty($aria))
            @foreach($aria as $attribute => $value)
                aria-{{ $attribute }}="{{ $value }}"
            @endforeach
        @endif
        @isset($w_click)
            wire:click="{{ $w_click }}"
        @endisset
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
