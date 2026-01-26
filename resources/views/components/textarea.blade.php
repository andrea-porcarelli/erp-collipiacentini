@props([
    'state' => 'default',
    'label' => null,
    'class' => null,
    'class_container' => null,
    'supporting_text' => '',
    'name' => '',
    'value' => null,
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
<div class="text-field {{ $class_container }}" data-mode="{{ $size }}">
    @isset($label)
        <label>{!! $label !!} @if($required)* @endif</label>
    @endisset
    <div class="text-field-container">
        <textarea
            class="input-miticko {{ $class }}"
            name="{{ $name }}"
            id="{{ $name }}"
            @if($required)
                required
            @endif
            @if($disabled)
                disabled
            @endif
        >{{ $default }}</textarea>
    </div>
    <x-supporting-text :message="$message" :icon="$icon"/>
</div>
