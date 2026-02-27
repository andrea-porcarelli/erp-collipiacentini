@props([
    'label' => null,
    'class' => null,
    'name' => '',
    'placeholder' => null,
    'value' => null,
    'type' => 'text',
    'size' => 'medium',
    'required' => false,
    'disabled' => false,
    'message' => null,
    'icon' => null,
    'model' => null,
    'maxlength' => null,
])
<div class="text-field">
    @isset($label)
        <label>{!! $label !!} @if($required)* @endif</label>
    @endisset
    <input
        class="js-switch {{ $class }}"
        name="{{ $name }}"
        id="{{ $name }}"
        type="checkbox"
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
    <x-supporting-text :message="$message" :icon="$icon"/>
</div>
