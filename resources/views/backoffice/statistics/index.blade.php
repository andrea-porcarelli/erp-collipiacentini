@extends('backoffice.layout', ['title' => 'Statistiche', 'active' => $active])

@section('main-content')
    @if($isGod)
        <x-header-page title="Statistiche" />
        <div class="w-100">
            <div class="row">
                <div class="col-12">
                    <x-card title="In costruzione" sub_title="Questa sezione sarà disponibile presto.">
                        <p class="text-muted mb-0">La dashboard statistiche per i ruoli god sarà definita successivamente.</p>
                    </x-card>
                </div>
            </div>
        </div>
    @else
        <div class="w-100 statistics-page">

            {{-- Intestazione --}}
            <div class="content-header">
                <small>Statistiche</small>
                <h1 class="mb-0">Fatturato</h1>
            </div>

            {{-- Pannello filtri --}}
            <x-card class="mt-spacing-l" brelative="true">
                <div class="row g-4 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-uppercase text-muted mb-2">Periodo</label>
                        <x-input
                            name="period"
                            leading="fa-calendar"
                            value="Ultimi 30 giorni"
                            disabled="true"
                        />
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small text-uppercase text-muted mb-2">Confronta con</label>
                        <div class="d-flex flex-wrap gap-2" data-toggle-group="compare">
                            <span class="chip-miticko" data-mode="chipAppearance-Active"   data-value="off">Disattivato</span>
                            <span class="chip-miticko" data-mode="chipAppearance-Inactive" data-value="previous_period">Periodo precedente</span>
                            <span class="chip-miticko" data-mode="chipAppearance-Inactive" data-value="previous_year">Anno precedente</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small text-uppercase text-muted mb-2">Origine dati</label>
                        <div class="d-flex flex-wrap gap-2" data-toggle-group="source">
                            <span class="chip-miticko" data-mode="chipAppearance-Active"   data-value="visit_date">Data visita</span>
                            <span class="chip-miticko" data-mode="chipAppearance-Inactive" data-value="order_date">Data ordine</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-1 text-end">
                        <x-button label="Esporta" leading="fa-download" status="Primary" emphasis="High" size="Medium" />
                    </div>
                </div>

                {{-- Chip filtri --}}
                <div class="filters-miticko mt-spacing-l">
                    <x-filter label="Stato ordine"            name="order_status"   type="status" />
                    <x-filter label="Categoria"               name="category"       type="status" />
                    <x-filter label="Master / Slave"          name="master_slave"   type="status" />
                    <x-filter label="Prodotti"                name="products"       type="status" />
                    <x-filter label="Varianti"                name="variants"       type="status" />
                    <x-filter label="Partner / Rivenditore"   name="partner"        type="status" />
                    <x-filter label="Metodo di pagamento"     name="payment_method" type="status" />
                    <x-filter label="Checkin"                 name="checkin"        type="status" />
                </div>
            </x-card>

            {{-- Sezione: Statistiche → Ordini --}}
            <div class="mt-spacing-xl">
                <div class="d-flex justify-content-between align-items-end mb-spacing-m">
                    <div>
                        <small class="text-uppercase text-muted">Statistiche</small>
                        <h3 class="mb-0">Ordini</h3>
                    </div>
                    <small class="text-muted"
                           title="I clienti unici sono aggregati per email: ordini con la stessa email contano come un solo cliente.">
                        <i class="fa-regular fa-circle-info"></i>
                        Clienti unici e ricorrenti sono calcolati per email
                    </small>
                </div>

                {{-- KPI economici --}}
                <div class="row g-3">
                    @php
                        $kpiCards = [
                            ['label' => 'Fatturato',             'value' => $kpi['revenue']],
                            ['label' => 'Margine netto',         'value' => $kpi['net_margin']],
                            ['label' => 'Commissioni',           'value' => $kpi['commissions']],
                            ['label' => 'Commissioni pagamento', 'value' => $kpi['payment_fees']],
                            ['label' => 'Prevendita',            'value' => $kpi['presale']],
                        ];
                    @endphp
                    @foreach($kpiCards as $card)
                        <div class="col-12 col-md-6 col-lg">
                            <x-card size="Small">
                                <div class="d-flex justify-content-between align-items-start">
                                    <small class="text-uppercase text-muted">{{ $card['label'] }}</small>
                                    <i class="fa-regular fa-circle-info text-muted" title="{{ $card['label'] }}"></i>
                                </div>
                                <h2 class="mt-spacing-s mb-0">
                                    {{ number_format($card['value'], 2, ',', '.') }} €
                                </h2>
                            </x-card>
                        </div>
                    @endforeach
                </div>

                {{-- KPI clienti (aggregazione per email) --}}
                <div class="row g-3 mt-spacing-s">
                    @php
                        $customerKpi = [
                            [
                                'label' => 'N. ordini',
                                'value' => $kpi['orders_count'],
                                'hint'  => 'Numero totale di ordini nel periodo.',
                            ],
                            [
                                'label' => 'Clienti unici',
                                'value' => $kpi['unique_customers'],
                                'hint'  => 'Numero di email distinte tra gli ordini del periodo.',
                            ],
                            [
                                'label' => 'Clienti ricorrenti',
                                'value' => $kpi['recurring_customers'],
                                'hint'  => 'Email che hanno effettuato più di un ordine nel periodo.',
                            ],
                        ];
                    @endphp
                    @foreach($customerKpi as $card)
                        <div class="col-12 col-md-4">
                            <x-card size="Small">
                                <div class="d-flex justify-content-between align-items-start">
                                    <small class="text-uppercase text-muted">{{ $card['label'] }}</small>
                                    <i class="fa-regular fa-circle-info text-muted" title="{{ $card['hint'] }}"></i>
                                </div>
                                <h2 class="mt-spacing-s mb-0">
                                    {{ number_format($card['value'], 0, ',', '.') }}
                                </h2>
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tabs + Grafico --}}
            <x-card class="mt-spacing-xl" title="Statistiche">
                <div class="d-flex flex-wrap gap-2 mb-spacing-l" data-toggle-group="stats-tab">
                    <span class="chip-miticko" data-mode="chipAppearance-Active"   data-value="revenue">Fatturato</span>
                    <span class="chip-miticko" data-mode="chipAppearance-Inactive" data-value="orders">Ordini</span>
                    <span class="chip-miticko" data-mode="chipAppearance-Inactive" data-value="visitors">Visitatori</span>
                </div>
                <div
                    id="statistics-chart-placeholder"
                    class="d-flex align-items-center justify-content-center"
                    style="
                        border: 2px dashed #e23b3b;
                        background: rgba(226,59,59,0.05);
                        border-radius: 12px;
                        min-height: 280px;
                        color: #e23b3b;
                        font-weight: 600;
                    "
                >
                    Grafico
                </div>
            </x-card>

            {{-- Tabella ordini --}}
            <x-card class="mt-spacing-xl" title="Visualizzazione tabella">
                <div class="table-responsive">
                    <table class="table-miticko">
                        <thead>
                            <tr>
                                <th>#ordine</th>
                                <th>Cliente</th>
                                <th>Data visita</th>
                                <th>Prodotto</th>
                                <th>Persone</th>
                                <th>Data ordine</th>
                                <th>Stato cliente</th>
                                <th>Stato pag.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $firstOp = $order->orderProducts->first();
                                    $bookingDate = $firstOp?->booking_date;
                                    $bookingTime = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : null;
                                    $persons = $order->orderProducts->flatMap->items->sum('quantity');
                                @endphp
                                <tr>
                                    <td>#MTK-{{ $order->order_number }}</td>
                                    <td>
                                        {{ $order->full_name }}
                                        @if($order->phone)
                                            <div class="text-muted small">{{ $order->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($bookingDate)
                                            {{ \Carbon\Carbon::parse($bookingDate)->translatedFormat('d M Y') }}
                                            @if($bookingTime)
                                                <div class="text-muted small">{{ $bookingTime }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        {{ $firstOp?->product?->label ?? '—' }}
                                        @if($firstOp?->product?->category?->label ?? false)
                                            <div class="text-muted small">{{ $firstOp->product->category->label }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $persons }}</td>
                                    <td>
                                        {{ $order->created_at?->translatedFormat('d/m/Y') }}
                                        <div class="text-muted small">{{ $order->created_at?->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        @if($order->customer_status)
                                            <x-label :appearance="$order->customer_status->status()" :icon="$order->customer_status->icon()">
                                                {{ $order->customer_status->label() }}
                                            </x-label>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->order_status)
                                            <x-label :appearance="$order->order_status->status()" :icon="$order->order_status->icon()">
                                                {{ $order->order_status->label() }}
                                            </x-label>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Nessun ordine nel periodo selezionato.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end align-items-center gap-2 mt-spacing-m text-muted small">
                    <span>ordini per pagina</span>
                    <select class="input-miticko" style="width: 70px;" disabled>
                        <option>10</option>
                    </select>
                </div>
            </x-card>

            {{-- Sezione: Statistiche → Clienti --}}
            <div class="mt-spacing-xl">
                <div class="d-flex justify-content-between align-items-end mb-spacing-m">
                    <div>
                        <small class="text-uppercase text-muted">Statistiche</small>
                        <h3 class="mb-0">Clienti</h3>
                    </div>
                    <small class="text-muted"
                           title="Le distribuzioni geografiche e anagrafiche sono calcolate sui dati dichiarati in ogni singolo ordine. Se la stessa email indica città diverse in ordini diversi, ciascun ordine viene contato nella città che ha dichiarato in quel momento.">
                        <i class="fa-regular fa-circle-info"></i>
                        Dati calcolati per singolo ordine, non per cliente
                    </small>
                </div>

                <div class="row g-3">
                    {{-- Città --}}
                    <div class="col-12 col-lg-4">
                        <x-card title="Città" sub_title="Top 20 per numero di ordini">
                            @if($orders_per_city->isEmpty())
                                <p class="text-muted mb-0 small">Nessun dato disponibile nel periodo.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table-miticko">
                                        <thead>
                                            <tr>
                                                <th>Città</th>
                                                <th class="text-end">Ordini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders_per_city as $row)
                                                <tr>
                                                    <td>{{ $row->city }}</td>
                                                    <td class="text-end">{{ $row->c }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </x-card>
                    </div>

                    {{-- Nazione --}}
                    <div class="col-12 col-lg-4">
                        <x-card title="Nazione" sub_title="Top 20 per numero di ordini">
                            @if($orders_per_country->isEmpty())
                                <p class="text-muted mb-0 small">Nessun dato disponibile nel periodo.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table-miticko">
                                        <thead>
                                            <tr>
                                                <th>Nazione</th>
                                                <th class="text-end">Ordini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders_per_country as $row)
                                                <tr>
                                                    <td>{{ $countries_by_id[$row->country_id] ?? '—' }}</td>
                                                    <td class="text-end">{{ $row->c }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </x-card>
                    </div>

                    {{-- Fasce d'età --}}
                    <div class="col-12 col-lg-4">
                        <x-card title="Fasce d'età" sub_title="Calcolate dalla data di nascita dichiarata">
                            @if($orders_per_age->isEmpty() && $orders_without_age === 0)
                                <p class="text-muted mb-0 small">Nessun dato disponibile nel periodo.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table-miticko">
                                        <thead>
                                            <tr>
                                                <th>Fascia</th>
                                                <th class="text-end">Ordini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders_per_age as $row)
                                                <tr>
                                                    <td>{{ $row['bucket'] }}</td>
                                                    <td class="text-end">{{ $row['count'] }}</td>
                                                </tr>
                                            @endforeach
                                            @if($orders_without_age > 0)
                                                <tr>
                                                    <td class="text-muted">Non dichiarata</td>
                                                    <td class="text-end text-muted">{{ $orders_without_age }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </x-card>
                    </div>
                </div>

                <p class="text-muted small mt-spacing-m mb-0">
                    <i class="fa-regular fa-circle-info me-1"></i>
                    Provincia e genere non sono attualmente raccolti nel form d'ordine e non compaiono in queste statistiche.
                </p>
            </div>

        </div>
    @endif
@endsection

@section('custom-script')
    <script>
        $(document).ready(function () {
            $(document).on('click', '[data-toggle-group] .chip-miticko', function () {
                const $group = $(this).closest('[data-toggle-group]');
                $group.find('.chip-miticko')
                    .attr('data-mode', 'chipAppearance-Inactive');
                $(this).attr('data-mode', 'chipAppearance-Active');
            });
        });
    </script>
@endsection
