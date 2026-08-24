@extends('backoffice.layout', ['title' => 'Fatturazione', 'active' => $path])

@section('main-content')
    <x-header-page title="Fatturazione" />

    <div class="w-100 mt-spacing-xl">
        {{-- Tabs navigation --}}
        <div class="d-flex gap-2 invoice-tabs-scroll" id="invoiceTabs" role="tablist">
            <x-chip
                label="Fatture emesse"
                :dataset="['tab-target' => '#invoices-issued-panel']"
            />
            <x-chip
                label="Ordini da fatturare"
                appearance="Resting"
                :dataset="['tab-target' => '#invoices-pending-panel']"
            />
        </div>

        <div class="tab-content mt-spacing-xl" id="invoiceTabsContent">
            {{-- ────────────────────────  FATTURE EMESSE  ──────────────────────── --}}
            <div class="tab-pane fade show active" id="invoices-issued-panel" role="tabpanel">
                <x-card title="Fatture emesse" sub_title="Storico dei documenti generati dal sistema" brelative="true">
                    <x-table-header>
                        <div class="filters-miticko" data-scope="issued">
                            <x-filter label="Data emissione" type="daterange" name="dates" />
                            <x-filter label="Partner" name="partners" type="partner" />
                            <x-filter label="Destinatario" name="recipient_type" type="recipient" />
                            <x-filter label="Stato fattura" name="status" type="invoicestatus" />
                        </div>
                        <span class="table-header-total-issued"> - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable-invoices">
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Data emissione</th>
                                    <th>Ordine</th>
                                    <th>Destinatario</th>
                                    <th>Nominativo</th>
                                    <th class="text-end">Totale</th>
                                    <th>Stato</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ────────────────────────  ORDINI DA FATTURARE  ──────────────────────── --}}
            <div class="tab-pane fade" id="invoices-pending-panel" role="tabpanel">
                <x-card title="Ordini da fatturare" sub_title="Ordini pagati senza alcuna fattura ancora generata" brelative="true">
                    <x-table-header>
                        <div class="filters-miticko" data-scope="pending">
                            <x-filter label="Data pagamento" type="daterange" name="dates" />
                            <x-filter label="Partner" name="partners" type="partner" />
                            <x-filter label="Cliente" type="text" name="customer" />
                        </div>
                        <span class="table-header-total-pending"> - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable-pending">
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

    {{-- Modali filtri riutilizzati dal pattern esistente --}}
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
    <x-modal id="filter-recipient" title="Filtra per destinatario" primary="Salva" secondary="annulla" width="400px">
        <ul class="order-statuses">
            <li><x-checkbox label="Partner" name="partner" /></li>
            <li><x-checkbox label="Cliente finale" name="customer" /></li>
        </ul>
    </x-modal>
    <x-modal id="filter-invoicestatus" title="Filtra per stato fattura" primary="Salva" secondary="annulla" width="400px">
        <ul class="order-statuses">
            @foreach($statuses as $value => $label)
                <li><x-checkbox :label="$label" :name="$value" /></li>
            @endforeach
        </ul>
    </x-modal>
@endsection

@section('custom-css')
    <style>
        .invoice-tabs-scroll {
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .datatable-invoices,
        .datatable-pending {
            width: 100% !important;
        }
    </style>
@endsection

@section('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script>
        $(document).ready(function () {
            // Datatable Fatture emesse
            $(document).trigger('datatable', [{
                selector: '.datatable-invoices',
                filtersScope: '#invoices-issued-panel',
                path: '{{ route('invoices.data') }}',
                columns: [
                    { data: 'number' },
                    { data: 'emitted_at' },
                    { data: 'order' },
                    { data: 'recipient_type_label' },
                    { data: 'recipient_name' },
                    { data: 'total_formatted', class: 'text-end' },
                    { data: 'status_label' },
                    { data: 'action', class: 'text-end' },
                ],
                drawCallback: function (api) {
                    const info = api.api.page.info();
                    $('.table-header-total-issued').html(`${info.recordsDisplay} fattur${info.recordsDisplay === 1 ? 'a' : 'e'}`);
                }
            }]);

            // Datatable Ordini da fatturare
            $(document).trigger('datatable', [{
                selector: '.datatable-pending',
                filtersScope: '#invoices-pending-panel',
                path: '{{ route('invoices.pending.data') }}',
                columns: [
                    { data: 'order_number' },
                    { data: 'paid_at' },
                    { data: 'partner' },
                    { data: 'customer' },
                    { data: 'product' },
                    { data: 'total_formatted', class: 'text-end' },
                    { data: 'action', class: 'text-end' },
                ],
                drawCallback: function (api) {
                    const info = api.api.page.info();
                    $('.table-header-total-pending').html(`${info.recordsDisplay} ordin${info.recordsDisplay === 1 ? 'e' : 'i'}`);
                }
            }]);

            // Tab switch (stesso pattern di products/show.blade.php)
            (function () {
                const chips = document.querySelectorAll('#invoiceTabs .chip-miticko');
                const panes = document.querySelectorAll('#invoiceTabsContent .tab-pane');
                const showTab = (target) => {
                    panes.forEach(p => { p.style.display = 'none'; p.style.opacity = '0'; });
                    const active = document.querySelector(target);
                    if (active) { active.style.display = 'block'; active.style.opacity = '1'; }
                };
                chips.forEach(chip => {
                    chip.addEventListener('click', function () {
                        const target = this.getAttribute('data-tab-target');
                        if (!target) return;
                        showTab(target);
                        chips.forEach(c => c.setAttribute('data-mode', 'chipAppearance-Resting'));
                        this.setAttribute('data-mode', 'chipAppearance-Active');
                    });
                });
            })();
        });
    </script>
@endsection
