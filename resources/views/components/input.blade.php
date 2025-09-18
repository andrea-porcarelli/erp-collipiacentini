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
    'type' => 'text',
])
<div class="text-field">
    @isset($label)
        <label>{!! $label !!}</label>
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
        />
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
    </div>
    <x-supporting-text />
</div>
