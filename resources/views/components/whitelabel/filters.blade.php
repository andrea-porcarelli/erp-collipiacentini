@props(['categories' => collect()])
<section class="row w-100">
    <div class="col-12 container-filters">
        <x-chip
            class="bt-chip btn-filter-products"
            :label="__('whitelabel.filters.all_products')"
            :dataset="['filter' => 'all']"
        />
        @foreach($categories as $category)
            <x-chip
                class="bt-chip btn-filter-products"
                :label="$category->label"
                :dataset="['filter' => $category->id]"
            />
        @endforeach
    </div>
</section>
