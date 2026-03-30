<!-- Footer -->
<section class="row w-100">
    <div class="col-12 container-filters">
        <x-chip
            class="bt-chip btn-filter-products"
            :label="__('whitelabel.filters.all_products')"
            :dataset="['filter' => 'all']"
        />
        <x-chip
            class="bt-chip btn-filter-products"
            :label="__('whitelabel.filters.guided_tour')"
            :dataset="['filter' => 'guided']"
        />
        <x-chip
            class="bt-chip btn-filter-products"
            :label="__('whitelabel.filters.free_tour')"
            :dataset="['filter' => 'free']"
        />
        <x-chip
            class="bt-chip"
            :label="__('whitelabel.filters.products')"
            :dataset="['filter' => 'free']"
        />
    </div>
</section>
