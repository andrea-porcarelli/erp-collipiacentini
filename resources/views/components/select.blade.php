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
    'required' => false,
    'message' => null,
    'icon' => null,
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
            @foreach($options as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
    </div>
    @isset($message)
        <x-supporting-text :message="$message" :icon="$icon"/>
    @endisset
</div>
