@props([
    'state' => 'default',
    'leading' => null,
    'leading_style' => 'solid',
    'trailing' => null,
    'trailing_style' => 'solid',
    'label' => null,
    'class' => null,
    'supporting_text' => '',
    'name' => '',
    'placeholder' => null,
    'value' => null,
    'type' => 'text',
    'size' => 'medium',
    'required' => false,
    'disabled' => false,
    'message' => null,
    'icon' => null,
    'model' => null
])
@php
    $default = null;
    if (isset($value)){
        $default = $value;
    }
    if (isset($model->{$name})){
        $default = $model->{$name};
    }
@endphp
<div class="text-field" data-mode="{{ ucfirst($size) }}">
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
            @isset($default)
                value="{{ $default }}"
            @endisset
            @if($required)
                required
            @endif
            @if($disabled)
                disabled
            @endif
        />
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
    </div>
    <x-supporting-text :message="$message" :icon="$icon"/>
</div>
