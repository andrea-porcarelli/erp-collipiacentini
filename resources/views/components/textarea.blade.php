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
$charCountMessage = $maxlength
    ? ($maxlength - strlen($default ?? '')) . ' / ' . $maxlength . ' caratteri rimanenti'
    : null;
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
                oninput="this.closest('.text-field').querySelector('.char-count').textContent = ({{ $maxlength }} - this.value.length) + ' / {{ $maxlength }} caratteri rimanenti'"
            @endif
        >{{ $default }}</textarea>
    </div>
    @if($maxlength)
        <div class="supporting-text-row">
            <x-supporting-text :message="$message" :icon="$icon"/>
            <x-supporting-text :message="$charCountMessage" extra_class="char-count"/>
        </div>
    @else
        <x-supporting-text :message="$message" :icon="$icon"/>
    @endif
</div>
