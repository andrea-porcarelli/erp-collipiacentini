@extends('whitelabel.layout', compact('company'))

@section('content')
    <div class="container mt-5">
        <div class="row w-100">
            <div class="col-12 text-center hero ">
                <h1>{{ __('whitelabel.hero.title', ['company' => $company->company_name]) }}</h1>
                <h5>{{ __('whitelabel.hero.subtitle') }}</h5>
            </div>
        </div>
        <div class="row w-100">
            <aside class="col-12 col-sm-3 sidebar">
                <x-whitelabel.sidebar :company="$company" />
            </aside>
            <div class="col-12 col-sm-9">
                <x-whitelabel.filters />
                <div class="d-flex mt-3">
                    <div class="col-12 products-list">
                        @if($products->count() === 0)
                            {{ __('whitelabel.products.no_availability') }}
                        @else
                            @foreach($products as $product)
                                <x-whitelabel.product :product="$product" />
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="d-block d-sm-none">
                    <x-card :title="__('whitelabel.sidebar.castle_title')" :sub_title="__('whitelabel.sidebar.know_date_subtitle')" class="card-spacing">

                    </x-card>

                    <x-card :title="__('whitelabel.sidebar.useful_links_title')" class="card-spacing">
                        <ul class="utils">
                            <li><a href="#">{{ __('whitelabel.sidebar.contacts') }}</a></li>
                            <li><a href="#">{{ __('whitelabel.sidebar.privacy_policy') }}</a></li>
                        </ul>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.btn-filter-products');
            const productsContainer = document.querySelector('.products-list');

            // Rendi la funzione globale per poterla chiamare da sidebar
            window.load_products = () => {
                // Leggi il filtro attivo dalla pagina
                const activeFilterButton = document.querySelector('.btn-filter-products.bt-m-default');
                const filter = activeFilterButton ? activeFilterButton.dataset.filter : null;

                // Leggi la data dal campo filter_date
                const filterDateInput = document.querySelector('input[name="filter_date"]');
                const date = filterDateInput && filterDateInput.value ? filterDateInput.value : null;

                let url = `/booking/filter-products?token={{ Session::get('token') }}`;

                if (filter) {
                    url += `&filter=${filter}`;
                }

                if (date) {
                    url += `&date=${date}`;
                }

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        productsContainer.innerHTML = data.html;
                    })
                    .catch(error => {
                        console.error('Errore nel caricamento dei prodotti:', error);
                    });
            }

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {

                    // Aggiorna lo stato dei bottoni
                    filterButtons.forEach(btn => {
                        // Rimuovi le classi di stato attivo
                        btn.classList.remove('bt-m-default');
                        btn.classList.add('bt-m-outlined');

                        // Rimuovi l'icona di check se presente
                        const icon = btn.querySelector('i.fa-check');
                        if (icon) {
                            icon.remove();
                        }
                    });

                    // Aggiungi lo stato attivo al bottone cliccato
                    this.classList.remove('bt-m-outlined');
                    this.classList.add('bt-m-default');

                    // Aggiungi l'icona di check
                    const checkIcon = document.createElement('i');
                    checkIcon.className = 'fa-solid fa-check';
                    this.insertBefore(checkIcon, this.firstChild);

                    // Chiamata AJAX per filtrare i prodotti
                    window.load_products()
                });
            });
        });
    </script>
@endpush
