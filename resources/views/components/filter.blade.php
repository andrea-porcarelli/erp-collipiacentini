@props([
    'label' => '',
    'type' => null,
    'title' => null,
    'name' => null,
])
<div class="filter" data-type="{{ $type }}" data-title="{{ $title }}" data-name="{{ $name }}" data-label="{{ $label }}" data-open="false">
    <span class="label-container">
        <span class="fa fa-square-plus"></span>
        <span class="label">{{ $label }}</span>
    </span>
    <input type="hidden" name="{{ $name }}" >
</div>
