@props([
    'product' => null
])
<x-card class="card-product">
    @php
        $productImage = $product->cover->first() ?? $product->gallery->first();
        $productImageUrl = $productImage
            ? asset('storage/' . $productImage->file_path)
            : asset('whitelabel/images/product.jpg');
    @endphp
    <div class="card-product-container @if(!$product->is_available) is-unavailable @endif">
        <figure>
            <img title="{{ $product->label }}" alt="{{ $product->label }}" src="{{ $productImageUrl }}">
        </figure>
        <section>
            <div class="product-detail">
                @if($product->is_available)
                    <x-label appearance="Success">DISPONIBILE</x-label>
                @else
                    <x-label appearance="Error">NON DISPONIBILE</x-label>
                @endif
                <h3 class="mt-2">{{ $product->label }}</h3>
                <div class=" d-none d-sm-block">
                    <div class="details">
                        <x-supporting-text icon="fa-regular fa-flag-swallowtail" :message="$product->type" />
                        <x-supporting-text icon="fa-regular fa-clock-three" :message="$product->duration . ' min'" />
                    </div>
                </div>
                <div class="description mt-spacing-l">
                    {{ $product->intro }}
                </div>
            </div>
            <div class="d-none d-sm-block">
                <div class="price">
                    <b>{{ Utils::price($product->lowest_price) }}</b>
                    <x-button
                        label="Acquista"
                        emphasis="High"
                        :status="!$product->is_available ? 'Disabled' : 'Primary'"
                        trailing="fa-ticket-perforated"
                        :disabled="!$product->is_available"
                        :href="$product->route"
                    />
                </div>
            </div>
        </section>
    </div>
    <div class="d-block d-sm-none">
        <div class="details-price">
            <div class="details">
                <x-supporting-text icon="fa-regular fa-flag-swallowtail" :message="$product->type" />
                <x-supporting-text icon="fa-regular fa-clock-three" :message="$product->duration . ' min'" />
            </div>
            <div class="price">
                <b>{{ Utils::price($product->lowest_price) }}</b>
                <x-button
                    label="Acquista"
                    trailing="fa-ticket-perforated"
                    :disabled="!$product->is_available"
                    :href="$product->route"
                />
            </div>
        </div>
    </div>
</x-card>
