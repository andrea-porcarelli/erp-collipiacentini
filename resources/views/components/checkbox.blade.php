@props([
    'type' => 'checkbox',
    'icon' => 'square',
    'label' => null,
    'name' => null,
    'disabled' => false,
])
<div class="checkbox-miticko @if($disabled) disabled @endif">
    @isset($icon)
        <span class="fa-regular fa-{{ $icon }}"></span>
    @endisset
    <span>
        {{ $label }}
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
</div>
