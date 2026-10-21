@props([
    'model' => null,
    'name' => '',
    'label' => null,
    'value' => null,
    'size' => 'Medium',
    'required' => false,
    'placeholder' => null,
    'class' => '',
])
@php
$vatOptions = collect(config('vat.rates', []))
    ->map(fn ($r) => ['id' => (int) $r['rate'], 'label' => $r['label']])
    ->all();
@endphp
<x-select
    :model="$model"
    :name="$name"
    :label="$label"
    :value="$value"
    :size="$size"
    :required="$required"
    :placeholder="$placeholder"
    :class="$class"
    :options="$vatOptions"
    {{ $attributes }}
/>
