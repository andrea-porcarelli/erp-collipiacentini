@props([
    'state' => 'default',
    'leading' => null,
    'leading_style' => 'solid',
    'trailing' => null,
    'trailing_style' => 'solid',
    'label' => null,
    'class' => '',
    'supporting_text' => '',
    'name' => '',
    'placeholder' => null,
    'value' => null,
    'type' => 'text',
    'size' => 'medium',
    'required' => false
])
<div class="text-field" data-mode="{{ $size }}">
    @isset($label)
        <label>{!! $label !!} @if($required)* @endif</label>
    @endisset
    <div class="text-field-container">
        @isset($leading)
            <i class="fa-{{ $leading_style }} {{ $leading }} icon"></i>
        @endisset
        <input
            class="input-miticko {{ $class }}"
            name="{{ $name }}"
            id="{{ $name }}"
            type="{{ $type }}"
            @isset($placeholder)
                placeholder="{{ $placeholder }}"
            @endisset
            @isset($value)
                value="{{ $value }}"
            @endisset
            @if($required)
                required
            @endif
        />
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
    </div>
    <x-supporting-text />
</div>
