@props([ 'active' => $active ?? null ])
<nav class="d-flex flex-column flex-shrink-0 sidebar">
    <img src="{{ asset('assets/images/logo-negativo.png') }}" class="logo">
    <div class="navigation-bar">
        <x-navigation-item label="Dashboard" icon="fa fa-house"  :is_active="$active === null" route="dashboard" />
        <x-navigation-item label="Ordini" icon="fa fa-receipt" route="orders.index" :is_active="$active === 'orders'"  />
        <x-navigation-item label="Prodotti" icon="fa fa-border-all" route="products.index" :is_active="$active === 'products'"  />
        <x-navigation-item label="Clienti" icon="fa fa-border-all" route="customers.index" :is_active="$active === 'customers'" />
        @if(Auth::user()->role == 'god')
            <x-navigation-item label="Categorie" icon="fa fa-border-all" route="categories.index" :is_active="$active === 'categories'"  />
            <x-navigation-item label="Statistiche" icon="fa fa-chart-line" />
            <x-navigation-item label="Impostazioni" icon="fa fa-gear" />
            <x-navigation-item label="Aziende" icon="fa fa-border-all"  route="companies.index" :is_active="$active === 'companies'"/>
            <x-navigation-item label="Partner" icon="fa fa-border-all" route="partners.index" :is_active="$active === 'partners'" />
            <x-navigation-item label="Utenti CP" icon="fa fa-border-all" route="users.index" :is_active="$active === 'users'" />
        @endif
    </div>
</nav>
