@props([
    'model' => null,
    'name' => '',
    'label' => null,
    'value' => null,
    'size' => 'Medium',
    'required' => false,
    'placeholder' => null,
    'class' => '',
    'only_numeric' => false,
])
@php
    $query = \App\Models\VatRate::where('is_active', 1)->orderBy('sort_order');
    if ($only_numeric) {
        $query->whereNull('nature');
    }
    $vatOptions = $query->get()
        ->map(fn ($r) => ['id' => $r->id, 'label' => $r->label])
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
