@props([
    'state' => 'default',
    'leading' => null,
    'leading_style' => 'solid',
    'trailing' => null,
    'trailing_style' => 'solid',
    'label' => null,
    'class' => null,
    'name' => '',
    'placeholder' => null,
    'value' => null,
    'type' => 'text',
    'size' => 'Medium',
    'appearance' => 'Resting',
    'required' => false,
    'disabled' => false,
    'message' => null,
    'icon' => null,
    'model' => null,
    'maxlength' => null,
    'extra' => null,
    'translate' => false
])
@php
    $default = null;
    if (isset($value)){
        $default = $value;
    }
    if (isset($model->{$name})){
        $default = $model->{$name};
    }
    $remaining = isset($maxlength) ? ($maxlength - mb_strlen($default ?? '')) : null;
@endphp
<div class="text-field" data-mode="textfieldSize-{{ $size }} textfieldAppearance-{{ $appearance }}">
    @isset($label)
        <label>{!! $label !!} @if($required)* @endif</label>
    @endisset
    <div class="text-field-container">
        @isset($leading)
            <i class="fa-{{ $leading_style }} {{ $leading }} icon"></i>
        @endisset
        <input
            {{ $attributes->whereStartsWith(['wire:', 'x-', '@', ':']) }}
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
            @if($maxlength)
                maxlength="{{ $maxlength }}"
                oninput="this.closest('.text-field').querySelector('.char-count').textContent = ({{ $maxlength }} - [...this.value].length) + ' / {{ $maxlength }} caratteri rimanenti'"
            @endif
        />
        @isset($trailing)
            <i class="fa-{{ $trailing_style }} {{ $trailing }} icon"></i>
        @endisset
        @isset($extra)
            <i class="extra">{{ $extra }}</i>
        @endisset
    </div>
    @if($maxlength)
        <x-supporting-text extra_class="char-count" :message="$remaining . ' / ' . $maxlength . ' caratteri rimanenti'"/>
    @endif
    @isset($message)
    <x-supporting-text :message="$message" :icon="$icon"/>
    @endisset
</div>
