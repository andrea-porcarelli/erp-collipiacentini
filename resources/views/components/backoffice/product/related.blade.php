@props(['model'])

@php
    $maxRelated = 5;
    $currentRelated = $model->relatedProducts()->with('relatedProduct')->get();
    $slots = collect(range(0, $maxRelated - 1))->map(fn($i) => $currentRelated->get($i)?->related_product_id);

    $availableProducts = \App\Models\Product::where('id', '!=', $model->id)
        ->when($model->partner_id, fn($q) => $q->where('partner_id', $model->partner_id))
        ->orderBy('label')
        ->get(['id', 'label']);
@endphp

@for($i = 0; $i < $maxRelated; $i++)
    <div class="{{ $i < $maxRelated - 1 ? 'mb-2' : '' }}">
        <div class="text-field" data-mode="textfieldSize-Medium">
            <div class="text-field-container">
                <select class="input-miticko related-slot-select" data-slot="{{ $i }}">
                    <option value="">Nessun prodotto</option>
                    @foreach($availableProducts as $product)
                        <option value="{{ $product->id }}" {{ $slots[$i] == $product->id ? 'selected' : '' }}>
                            {{ $product->label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endfor
