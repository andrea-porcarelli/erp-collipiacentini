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
            <img alt="{{ $product->image_alt }}" src="{{ $productImageUrl }}">
        </figure>
        <section>
            <div class="product-detail mr-spacing-4xl">
                @if($product->is_available)
                    <x-label appearance="Success">DISPONIBILE</x-label>
                @else
                    <x-label appearance="Error">NON DISPONIBILE</x-label>
                @endif
                <h2 class="mt-2">{{ $product->contentField('short_title') }}</h2>
                <div class=" d-none d-sm-block">
                    <div class="details">
                        <x-supporting-text icon="fa-regular fa-flag-swallowtail" :message="$product->category->label" />
                        <x-supporting-text icon="fa-regular fa-clock-three" :message="$product->duration . ' min'" />
                    </div>
                </div>
                <div class="description mt-spacing-l">
                    {{ $product->contentField('short_description') }}
                </div>
            </div>
            <div class="d-none d-sm-block">
                <div class="price">
                    <b>{{ $product->is_free ? __('whitelabel.products.free') : Utils::price($product->lowest_price_with_commission) }}</b>
                    <x-button
                        :label="$product->is_free ? __('whitelabel.products.book') : __('whitelabel.products.buy')"
                        emphasis="High"
                        status="Primary"
                        trailing="fa-ticket-perforated"
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
                <b>{{ $product->is_free ? __('whitelabel.products.free') : Utils::price($product->lowest_price_with_commission) }}</b>
                <x-button
                    :label="$product->is_free ? __('whitelabel.products.book') : __('whitelabel.products.buy')"
                    trailing="fa-ticket-perforated"
                    :href="$product->route"
                />
            </div>
        </div>
    </div>
</x-card>
