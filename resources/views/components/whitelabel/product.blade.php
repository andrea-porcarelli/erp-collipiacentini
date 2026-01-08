@props([
    'product' => null
])
<x-card class="card-product">
    <div class="card-product-container @if(!$product->is_available) is-unavailable @endif">
        <figure>
            <img title="Product" alt="Product" src="{{ asset('whitelabel/images/product.jpg') }}">
        </figure>
        <section>
            @if($product->is_available)
                <x-label status="success">DISPONIBILE</x-label>
            @else
                <x-label status="error">NON DISPONIBILE</x-label>
            @endif
            <h3 class="mt-2">{{ $product->label }}</h3>
            <div class=" d-none d-sm-block">
                <div class="details">
                    <x-supporting-text icon="fa-regular fa-flag-swallowtail" :message="$product->type" />
                    <x-supporting-text icon="fa-regular fa-clock-three" :message="$product->duration . ' min'" />
                </div>
            </div>
            <div class="description">
                {{ $product->intro }}
            </div>
        </section>
        <div class="d-none d-sm-block">
            <div class="price">
                <b>{{ Utils::price($product->lowest_price) }}</b>
                <x-button
                    label="Acquista"
                    trailing="fa-ticket-perforated"
                    :status="$product->button"
                    :disabled="!$product->is_available"
                    :href="$product->route"
                />
            </div>
        </div>
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
                    status="primary"
                    size="small"
                    :disabled="!$product->is_available"
                    :href="$product->route"
                />
            </div>
        </div>
    </div>
</x-card>
