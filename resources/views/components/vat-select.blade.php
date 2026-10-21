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
// `rate` è stringa per supportare sia aliquote numeriche ("22", "10", "0") sia nature
// di esenzione / non imponibilità per fattura elettronica ("N6.9", "N3.2", …).
$vatOptions = collect(config('vat.rates', []))
    ->map(fn ($r) => ['id' => (string) $r['rate'], 'label' => $r['label']])
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
