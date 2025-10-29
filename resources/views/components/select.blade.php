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
    'options' => [],
    'type' => 'text',
    'size' => 'medium',
])
<div class="text-field" data-mode="{{ $size }}">
    @isset($label)
        <label>{!! $label !!}</label>
    @endisset
    <div class="text-field-container">
        @isset($leading)
            <i class="fa-{{ $leading_style }} {{ $leading }} icon"></i>
        @endisset
        <select
            class="input-miticko {{ $class }}"
            name="{{ $name }}"
            id="{{ $name }}"
        >
            <option value="">Scegli</option>
            @foreach($options as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
        </select>
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
    </div>
    <x-supporting-text />
</div>
