@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => 'orders'])

@section('main-content')
    <x-header-page title="Ordini" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Lista ordini" sub_title="visualizza gli ordini che hai ricevuto">
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Data" />
                            <x-filter label="Tipo di acquisto" />
                            <x-filter label="Stato" />
                        </div>
                        <span class="table-header-total">12 ordini</span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko">
                            <thead>
                            <tr>
                                <th>#ordine</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Acquisto</th>
                                <th>Tipologia</th>
                                <th>Stato</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
