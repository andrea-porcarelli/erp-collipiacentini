@props([ 'active' => $active ?? null ])
<nav class="d-flex flex-column flex-shrink-0 sidebar">
    <img src="{{ asset('assets/images/logo-negativo.png') }}" class="logo">
    <div class="navigation-bar">
        <x-navigation-item label="Dashboard" icon="fa fa-house"  :is_active="$active === null" route="dashboard" />
        <x-navigation-item label="Prodotti" icon="fa fa-border-all"  />
        <x-navigation-item label="Ordini" icon="fa fa-receipt" route="orders.index" :is_active="$active === 'orders'"  />
        <x-navigation-item label="Statistiche" icon="fa fa-chart-line" />
        <x-navigation-item label="Impostazioni" icon="fa fa-gear" />
        <x-navigation-item label="Aziende" icon="fa fa-border-all" />
        <x-navigation-item label="Partner" icon="fa fa-border-all" />
    </div>
</nav>
