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
    'model' => null,
    'rows' => null,
    'maxlength' => null,
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
            @if($rows)
                rows="{{ $rows }}"
            @endif
            @if($maxlength)
                maxlength="{{ $maxlength }}"
                oninput="this.closest('.text-field').querySelector('.char-count').textContent = ({{ $maxlength }} - [...this.value].length) + ' / {{ $maxlength }} caratteri rimanenti'"
            @endif
        >{{ $default }}</textarea>
    </div>
    @if($maxlength)
        <x-supporting-text extra_class="char-count" :message="$remaining . ' / ' . $maxlength . ' caratteri rimanenti'"/>
    @endif
    @isset($message)
        <x-supporting-text :message="$message" :icon="$icon"/>
    @endisset
</div>
