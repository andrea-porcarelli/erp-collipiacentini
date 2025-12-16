<!-- Footer -->
<section class="row w-100">
    <div class="col-12 container-filters">
        <x-button :label="__('whitelabel.filters.all_products')" status="secondary" leading="fa-check" class="bt-chip btn-filter-products" :dataset="['filter' => 'all']" />
        <x-button :label="__('whitelabel.filters.guided_tour')" status="secondary" emphasis="outlined" class="bt-chip btn-filter-products" :dataset="['filter' => 'guided']" />
        <x-button :label="__('whitelabel.filters.free_tour')" status="secondary" emphasis="outlined" class="bt-chip btn-filter-products" :dataset="['filter' => 'free']" />
        <x-button :label="__('whitelabel.filters.products')" status="secondary" emphasis="outlined" class="bt-chip"/>
    </div>
</section>
