@extends('backoffice.layout', ['title' => 'Fatturazione', 'active' => $path])

@section('main-content')
    <x-header-page title="Fatturazione" />

    @include('backoffice.invoices._tabs', ['active' => 'pending'])

    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Ordini da fatturare" sub_title="Ordini pagati senza alcuna fattura ancora generata" brelative="true">
                    <x-table-header>
                        <div class="filters-miticko">
                            <x-filter label="Data pagamento" type="daterange" name="dates" />
                            <x-filter label="Partner" name="partners" type="partner" />
                            <x-filter label="Cliente" type="text" name="customer" />
                        </div>
                        <span class="table-header-total"> - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                                <tr>
                                    <th>Ordine</th>
                                    <th>Pagato il</th>
                                    <th>Partner</th>
                                    <th>Cliente</th>
                                    <th>Prodotto</th>
                                    <th class="text-end">Totale</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    {{-- Modali filtri --}}
    <x-modal id="filter-text" title="Filtra" primary="Salva" secondary="annulla" width="400px">
        <x-input name="filter_text_value" label="Cerca" placeholder="Digita per filtrare" />
    </x-modal>
    <x-modal id="filter-daterange" title="Seleziona periodo" primary="Salva" secondary="annulla" width="350px">
        <div class="d-flex align-items-center justify-content-center">
            <div id="calendar-container" data-filter="dates"></div>
        </div>
    </x-modal>
    <x-modal id="filter-partner" title="Filtra per partner" primary="Salva" secondary="annulla" width="400px">
        <ul class="order-statuses">
            @foreach($partners as $id => $name)
                <li><x-checkbox :label="$name" :name="(string) $id" /></li>
            @endforeach
        </ul>
    </x-modal>
@endsection

@section('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script>
        $(document).ready(function () {
            setTimeout(() => {
                $(document).trigger('datatable', [{
                    columns: [
                        { data: 'order_number' },
                        { data: 'paid_at' },
                        { data: 'partner' },
                        { data: 'customer' },
                        { data: 'product' },
                        { data: 'total_formatted', class: 'text-end' },
                        { data: 'action', class: 'text-end' },
                    ],
                    path: '{{ route('invoices.pending.data') }}',
                    drawCallback: function (api) {
                        const info = api.api.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} ordin${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                    }
                }]);
            });
        });
    </script>
@endsection
