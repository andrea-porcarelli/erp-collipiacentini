<div class="d-flex gap-3 product-tags">
    <x-supporting-text icon="fa-regular fa-ticket-perforated" :message="Utils::price($product->lowest_price)" />
    <x-supporting-text icon="fa-regular fa-clock-three" :message="$product->duration .' min'" />
    <x-supporting-text icon="fa-regular fa-flag-swallowtail" :message="$product->type" />
</div>
