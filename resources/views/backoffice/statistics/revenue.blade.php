@extends('backoffice.layout', ['title' => 'Statistiche', 'active' => $active])

@php
    use App\Services\Statistics\RevenueFilters;
    $queryBase = $filters->toQuery();
    if ($isMitickoAdmin && $selectedPartnerId) {
        $queryBase['partner_id'] = $selectedPartnerId;
    }
    $exportUrl = route('statistics.revenue.export', $queryBase);
    $periodPresetOptions = [
        'today'         => 'Oggi',
        'yesterday'     => 'Ieri',
        'last_7_days'   => 'Ultimi 7 giorni',
        'last_30_days'  => 'Ultimi 30 giorni',
        'last_90_days'  => 'Ultimi 90 giorni',
        'this_month'    => 'Mese corrente',
        'last_month'    => 'Mese scorso',
        'this_year'     => 'Anno in corso',
        'custom'        => 'Personalizzato…',
    ];
    $hasCompare = $filters->compare !== RevenueFilters::COMPARE_OFF && $kpiPrev;
    $deltaFn = function (float $curr, float $prev): ?array {
        if ($prev <= 0 && $curr <= 0) return null;
        if ($prev <= 0) return ['pct' => null, 'dir' => $curr > 0 ? 'up' : 'flat'];
        $pct = (($curr - $prev) / $prev) * 100;
        return ['pct' => $pct, 'dir' => $pct > 0.01 ? 'up' : ($pct < -0.01 ? 'down' : 'flat')];
    };
@endphp

@section('main-content')
        <div class="w-100 statistics-page">

            {{-- Intestazione --}}
            <div class="content-header d-flex justify-content-between align-items-start">
                <div>
                    <small>Statistiche</small>
                    <h1 class="mb-0">Fatturato</h1>
                </div>
                <x-button
                    id="btn-open-generate-report"
                    label="Genera report"
                    trailing="fa-file"
                    status="Primary"
                    emphasis="Low"
                    size="Medium"
                    :disabled="$noPartnerContext ?? false"
                />
            </div>

            @if($isMitickoAdmin)
                {{-- Selettore Partner (admin Miticko) --}}
                @php
                    $partnerSwitcherOptions = $allPartners
                        ->map(fn ($p) => ['id' => $p->id, 'label' => $p->partner_name])
                        ->values()
                        ->all();
                @endphp
                <x-card class="mt-spacing-l">
                    <div class="row g-4 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-uppercase text-muted mb-2">Partner</label>
                            <x-select
                                name="statistics_partner_switcher"
                                leading="fa-store"
                                :options="$partnerSwitcherOptions"
                                :value="$selectedPartnerId"
                            />
                        </div>
                        @if($noPartnerContext)
                            <div class="col-12 col-md-6 text-muted small">
                                <i class="fa-regular fa-circle-info me-1"></i>
                                Scegli un partner dall'elenco per sbloccare filtri, dati e report.
                            </div>
                        @endif
                    </div>
                </x-card>
            @endif

            <div class="statistics-content-wrap @if($noPartnerContext) locked @endif">

            {{-- Pannello filtri --}}
            <form id="revenue-filters" method="GET" action="{{ route('statistics.revenue') }}" autocomplete="off">
                @if($isMitickoAdmin && $selectedPartnerId)
                    <input type="hidden" name="partner_id" value="{{ $selectedPartnerId }}">
                @endif
                <input type="hidden" name="preset"  id="rf-preset"  value="{{ $filters->preset }}">
                <input type="hidden" name="from"    id="rf-from"    value="{{ $filters->from->toDateString() }}">
                <input type="hidden" name="to"      id="rf-to"      value="{{ $filters->to->toDateString() }}">
                <input type="hidden" name="compare" id="rf-compare" value="{{ $filters->compare }}">
                <input type="hidden" name="source"  id="rf-source"  value="{{ $filters->source }}">
                <div id="rf-multi-hidden">
                    @foreach($filters->orderStatus as $v)
                        <input type="hidden" name="order_status[]" value="{{ $v }}">
                    @endforeach
                    @foreach($filters->categoryIds as $v)
                        <input type="hidden" name="category_id[]" value="{{ $v }}">
                    @endforeach
                    @foreach($filters->productIds as $v)
                        <input type="hidden" name="product_id[]" value="{{ $v }}">
                    @endforeach
                    @foreach($filters->partnerIds as $v)
                        <input type="hidden" name="partner_id_filter[]" value="{{ $v }}">
                    @endforeach
                </div>

                <x-card class="mt-spacing-l" brelative="true">
                    <div class="row g-4 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-uppercase text-muted mb-2">Periodo</label>
                            <div class="text-field" data-mode="textfieldSize-Medium">
                                <div class="text-field-container">
                                    <i class="fa-solid fa-calendar icon"></i>
                                    <select class="input-miticko" id="rf-period-preset">
                                        @foreach($periodPresetOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($filters->preset === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="rf-period-custom" class="mt-spacing-s" @if($filters->preset !== 'custom') style="display:none;" @endif>
                                <div class="text-field" data-mode="textfieldSize-Medium">
                                    <div class="text-field-container">
                                        <i class="fa-regular fa-calendar-range icon"></i>
                                        <input type="text" class="input-miticko flatpickr-input" id="rf-range" placeholder="Seleziona intervallo"
                                               value="{{ $filters->preset === 'custom' ? $filters->from->format('d/m/Y').' - '.$filters->to->format('d/m/Y') : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-uppercase text-muted mb-2">Confronta con</label>
                            <div class="d-flex flex-wrap gap-2" data-toggle-group="compare">
                                <span class="chip-miticko" data-mode="{{ $filters->compare === 'off' ? 'chipAppearance-Active' : 'chipAppearance-Inactive' }}" data-value="off">Disattivato</span>
                                <span class="chip-miticko" data-mode="{{ $filters->compare === 'previous_period' ? 'chipAppearance-Active' : 'chipAppearance-Inactive' }}" data-value="previous_period">Periodo precedente</span>
                                <span class="chip-miticko" data-mode="{{ $filters->compare === 'previous_year' ? 'chipAppearance-Active' : 'chipAppearance-Inactive' }}" data-value="previous_year">Anno precedente</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-uppercase text-muted mb-2">Origine dati</label>
                            <div class="d-flex flex-wrap gap-2" data-toggle-group="source">
                                <span class="chip-miticko" data-mode="{{ $filters->source === 'visit_date' ? 'chipAppearance-Active' : 'chipAppearance-Inactive' }}" data-value="visit_date">Data visita</span>
                                <span class="chip-miticko" data-mode="{{ $filters->source === 'order_date' ? 'chipAppearance-Active' : 'chipAppearance-Inactive' }}" data-value="order_date">Data ordine</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-1 text-end">
                            <a href="{{ $exportUrl }}">
                            <x-button
                                label="Esporta"
                                leading="fa-download"
                                status="Primary"
                                emphasis="Medium"
                                size="Medium"
                            />
                            </a>
                        </div>
                    </div>

                    {{-- Chip filtri multi-select --}}
                    <div class="filters-miticko mt-spacing-l d-flex flex-wrap gap-2">
                        @include('backoffice.statistics.partials.filter-chip', [
                            'name' => 'order_status', 'label' => 'Stato ordine',
                            'options' => $statusOptions, 'selected' => $filters->orderStatus,
                        ])
                        @include('backoffice.statistics.partials.filter-chip', [
                            'name' => 'category_id', 'label' => 'Categoria',
                            'options' => $categoryOptions, 'selected' => $filters->categoryIds,
                        ])
                        <span class="chip-miticko" data-mode="chipAppearance-Disabled" title="Prossimamente">Master / Slave</span>
                        @include('backoffice.statistics.partials.filter-chip', [
                            'name' => 'product_id', 'label' => 'Prodotti',
                            'options' => $productOptions, 'selected' => $filters->productIds,
                        ])
                        <span class="chip-miticko" data-mode="chipAppearance-Disabled" title="Prossimamente">Varianti</span>
                        @if($isMitickoAdmin && $partnerFilterOptions->isNotEmpty())
                            @include('backoffice.statistics.partials.filter-chip', [
                                'name' => 'partner_id_filter', 'label' => 'Partner / Rivenditore',
                                'options' => $partnerFilterOptions, 'selected' => $filters->partnerIds,
                            ])
                        @else
                            <span class="chip-miticko" data-mode="chipAppearance-Disabled" title="Disponibile solo per admin Miticko">Partner / Rivenditore</span>
                        @endif
                        <span class="chip-miticko" data-mode="chipAppearance-Disabled" title="Prossimamente">Metodo di pagamento</span>
                        <span class="chip-miticko" data-mode="chipAppearance-Disabled" title="Prossimamente">Checkin</span>
                    </div>
                </x-card>
            </form>

            {{-- Riepilogo --}}
            <div class="mt-spacing-xl">
                <h3 class="mb-spacing-m">Riepilogo</h3>

                <div class="row g-3">
                    @php
                        $kpiCards = [
                            ['key' => 'revenue',      'label' => 'Fatturato',             'value' => $kpi['revenue'],      'suffix' => ' €'],
                            ['key' => 'net_margin',   'label' => 'Margine netto',         'value' => $kpi['net_margin'],   'suffix' => ' €'],
                            ['key' => 'commissions',  'label' => 'Commissioni',           'value' => $kpi['commissions'],  'suffix' => ' €'],
                            ['key' => 'payment_fees', 'label' => 'Commissioni pagamento', 'value' => $kpi['payment_fees'], 'suffix' => ' €'],
                            ['key' => 'presale',      'label' => 'Prevendita',            'value' => $kpi['presale'],      'suffix' => ' €'],
                        ];
                    @endphp
                    @foreach($kpiCards as $card)
                        @php
                            $delta = $hasCompare ? $deltaFn((float) $card['value'], (float) ($kpiPrev[$card['key']] ?? 0)) : null;
                        @endphp
                        <div class="col-12 col-md-6 col-lg">
                            <x-card size="Small">
                                <div class="d-flex justify-content-between align-items-start">
                                    <small class="text-uppercase text-muted">{{ $card['label'] }}</small>
                                    <i class="fa-regular fa-circle-info text-muted" title="{{ $card['label'] }}"></i>
                                </div>
                                <h2 class="mt-spacing-s mb-0">
                                    {{ number_format($card['value'], 2, ',', '.') }}{{ $card['suffix'] }}
                                </h2>
                                @if($delta)
                                    <div class="kpi-delta kpi-delta-{{ $delta['dir'] }} mt-spacing-xs">
                                        <i class="fa-solid fa-arrow-{{ $delta['dir'] === 'up' ? 'up' : ($delta['dir'] === 'down' ? 'down' : 'right') }}"></i>
                                        {{ $delta['pct'] === null ? 'nuovo' : number_format($delta['pct'], 1, ',', '.').' %' }}
                                        <small class="text-muted ms-1">vs {{ number_format((float) ($kpiPrev[$card['key']] ?? 0), 2, ',', '.') }}{{ $card['suffix'] }}</small>
                                    </div>
                                @endif
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tabs + Grafico --}}
            <x-card class="mt-spacing-xl" title="Statistiche">
                <div class="d-flex flex-wrap gap-2 mb-spacing-l" data-toggle-group="stats-tab" data-toggle-mode="multi">
                    <span class="chip-miticko" data-mode="chipAppearance-Active" data-value="revenue">Fatturato</span>
                    <span class="chip-miticko" data-mode="chipAppearance-Active" data-value="orders">Ordini</span>
                    <span class="chip-miticko" data-mode="chipAppearance-Active" data-value="visitors">Visitatori</span>
                </div>
                <div id="statistics-chart" style="min-height: 320px;"></div>
            </x-card>

            {{-- Tabella ordini (DataTables server-side) --}}
            <x-card class="mt-spacing-xl" title="Visualizzazione tabella">
                <table id="revenue-table" class="datatable table-miticko" style="width:100%;">
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
                </table>
            </x-card>

            </div>{{-- /.statistics-content-wrap --}}

            @include('backoffice.statistics.partials.generate-report-modal', [
                'reportPartners' => $reportPartners,
                'reportCurrentPartnerId' => $reportCurrentPartnerId,
            ])

        </div>
@endsection

@section('custom-css')
    <link rel="stylesheet" href="/backoffice/vendor/switchery/switchery.min.css" />
    <style>
        .statistics-content-wrap { position: relative; transition: opacity .2s ease; }
        .statistics-content-wrap.locked {
            opacity: .45;
            pointer-events: none;
            user-select: none;
        }
        .statistics-content-wrap.locked::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .35);
            z-index: 2;
        }
        .kpi-delta { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
        .kpi-delta-up    { color: #16a34a; }
        .kpi-delta-down  { color: #dc2626; }
        .kpi-delta-flat  { color: #6b7280; }
        .chip-filter {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .chip-filter-menu {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            z-index: 1050;
            min-width: 240px;
            max-height: 320px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            padding: 8px;
            display: none;
        }
        .chip-filter.is-open .chip-filter-menu { display: block; }
        .chip-filter-menu .chip-filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .chip-filter-menu .chip-filter-item:hover { background: #f3f4f6; }
        .chip-filter-menu .chip-filter-actions {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            padding: 8px 4px 2px;
            border-top: 1px solid #e5e7eb;
            margin-top: 6px;
        }
        .chip-filter-menu .chip-filter-actions .btn-link {
            background: none; border: 0; color: #6b7280; font-size: 12px; cursor: pointer; padding: 0;
        }
        .chip-filter-menu .chip-filter-actions .btn-apply {
            background: #111827; color: #fff; border: 0; border-radius: 6px; padding: 6px 10px; font-size: 12px; cursor: pointer;
        }
    </style>
@endsection

@section('custom-script')
    <script src="/backoffice/vendor/switchery/switchery.min.js"></script>
    <script src="/backoffice/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="/backoffice/vendor/datatables/dataTables.js"></script>
    <script>
        $(document).ready(function () {
            const $form = $('#revenue-filters');

            // --- Periodo preset
            $('#rf-period-preset').on('change', function () {
                const v = $(this).val();
                $('#rf-preset').val(v);
                if (v === 'custom') {
                    $('#rf-period-custom').show();
                    return; // non submit finché l'utente non seleziona range
                }
                $('#rf-period-custom').hide();
                $form.trigger('submit');
            });

            // --- Date range picker (flatpickr) per periodo custom
            if (window.flatpickr) {
                flatpickr('#rf-range', {
                    mode: 'range',
                    dateFormat: 'd/m/Y',
                    locale: 'it',
                    defaultDate: [$('#rf-from').val(), $('#rf-to').val()],
                    onClose: function (dates) {
                        if (dates.length === 2) {
                            const fmt = d => d.toISOString().slice(0, 10);
                            $('#rf-from').val(fmt(dates[0]));
                            $('#rf-to').val(fmt(dates[1]));
                            $('#rf-preset').val('custom');
                            $form.trigger('submit');
                        }
                    }
                });
            }

            // --- Toggle gruppi (compare, source, stats-tab)
            $(document).on('click', '[data-toggle-group] .chip-miticko:not([data-mode="chipAppearance-Disabled"])', function () {
                const $group = $(this).closest('[data-toggle-group]');
                const groupName = $group.data('toggle-group');
                const multi = $group.data('toggle-mode') === 'multi';
                const value = $(this).data('value');

                if (multi) {
                    const isActive = $(this).attr('data-mode') === 'chipAppearance-Active';
                    $(this).attr('data-mode', isActive ? 'chipAppearance-Inactive' : 'chipAppearance-Active');
                } else {
                    $group.find('.chip-miticko').not('[data-mode="chipAppearance-Disabled"]').attr('data-mode', 'chipAppearance-Inactive');
                    $(this).attr('data-mode', 'chipAppearance-Active');
                }

                if (groupName === 'compare') {
                    $('#rf-compare').val(value);
                    $form.trigger('submit');
                } else if (groupName === 'source') {
                    $('#rf-source').val(value);
                    $form.trigger('submit');
                } else if (groupName === 'stats-tab') {
                    toggleChartMetric(value);
                }
            });

            // --- Chip filtri multi-select
            $(document).on('click', '.chip-filter-trigger', function (e) {
                e.stopPropagation();
                const $chip = $(this).closest('.chip-filter');
                $('.chip-filter').not($chip).removeClass('is-open');
                $chip.toggleClass('is-open');
            });
            $(document).on('click', '.chip-filter-menu', function (e) { e.stopPropagation(); });
            $(document).on('click', function () { $('.chip-filter').removeClass('is-open'); });

            $(document).on('click', '.chip-filter .btn-apply', function () {
                const $chip = $(this).closest('.chip-filter');
                const name = $chip.data('name');
                $('#rf-multi-hidden input[name="' + name + '[]"]').remove();
                $chip.find('input[type=checkbox]:checked').each(function () {
                    $('#rf-multi-hidden').append($('<input>', { type: 'hidden', name: name + '[]', value: this.value }));
                });
                $form.trigger('submit');
            });
            $(document).on('click', '.chip-filter .btn-clear', function () {
                const $chip = $(this).closest('.chip-filter');
                const name = $chip.data('name');
                $chip.find('input[type=checkbox]').prop('checked', false);
                $('#rf-multi-hidden input[name="' + name + '[]"]').remove();
                $form.trigger('submit');
            });

            // --- ApexCharts (multi-serie, ottimizzato)
            const seriesData = @json($series);
            const metrics = ['revenue', 'orders', 'visitors'];
            const palette = { revenue: '#111827', orders: '#2563eb', visitors: '#16a34a' };
            const seriesLabel = { revenue: 'Fatturato', orders: 'Ordini', visitors: 'Visitatori' };
            const prevSuffix = ' (confronto)';
            const compareActive = seriesData.prev_revenue.some(v => v > 0)
                || seriesData.prev_orders.some(v => v > 0)
                || seriesData.prev_visitors.some(v => v > 0);
            const fmtEur = v => Number(v).toLocaleString('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' €';
            const fmtEurFull = v => Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            const fmtInt = v => Math.round(Number(v)).toLocaleString('it-IT');

            function buildSeries() {
                const series = metrics.map(m => ({
                    name: seriesLabel[m],
                    data: seriesData[m] || [],
                    type: 'line',
                }));
                if (compareActive) {
                    metrics.forEach(m => series.push({
                        name: seriesLabel[m] + prevSuffix,
                        data: seriesData['prev_' + m] || [],
                        type: 'line',
                    }));
                }
                return series;
            }

            const chartOptions = {
                chart: {
                    height: 340,
                    type: 'line',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: false },
                    redrawOnWindowResize: false,
                    redrawOnParentResize: false,
                },
                series: buildSeries(),
                xaxis: {
                    categories: seriesData.labels,
                    type: 'datetime',
                    labels: { datetimeFormatter: { year: 'yyyy', month: "MMM 'yy", day: 'dd MMM' } },
                },
                yaxis: [
                    {
                        seriesName: compareActive
                            ? [seriesLabel.revenue, seriesLabel.revenue + prevSuffix]
                            : seriesLabel.revenue,
                        title: { text: 'Fatturato (€)' },
                        labels: { formatter: fmtEur },
                    },
                    {
                        seriesName: compareActive
                            ? [seriesLabel.orders, seriesLabel.visitors, seriesLabel.orders + prevSuffix, seriesLabel.visitors + prevSuffix]
                            : [seriesLabel.orders, seriesLabel.visitors],
                        opposite: true,
                        title: { text: 'Ordini / Visitatori' },
                        labels: { formatter: fmtInt },
                    },
                ],
                stroke: {
                    curve: 'smooth',
                    width: compareActive ? [3,3,3,2,2,2] : [3,3,3],
                    dashArray: compareActive ? [0,0,0,6,6,6] : [0,0,0],
                },
                colors: compareActive
                    ? [palette.revenue, palette.orders, palette.visitors, palette.revenue, palette.orders, palette.visitors]
                    : [palette.revenue, palette.orders, palette.visitors],
                dataLabels: { enabled: false },
                markers: { size: 0, hover: { size: 4 } },
                tooltip: {
                    x: { format: 'dd MMM yyyy' },
                    y: {
                        formatter: function (val, opts) {
                            const name = (opts && opts.w && opts.w.config.series[opts.seriesIndex].name) || '';
                            return name.startsWith(seriesLabel.revenue) ? fmtEurFull(val) : fmtInt(val);
                        },
                    },
                },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
                legend: { show: true, position: 'top', horizontalAlign: 'right' },
            };

            let chartInstance = null;
            function toggleChartMetric(metric) {
                if (!chartInstance) return;
                const names = [seriesLabel[metric]];
                if (compareActive) names.push(seriesLabel[metric] + prevSuffix);
                names.forEach(n => chartInstance.toggleSeries(n));
            }

            @if(!$noPartnerContext)
                requestAnimationFrame(function () {
                    chartInstance = new ApexCharts(document.querySelector('#statistics-chart'), chartOptions);
                    chartInstance.render();
                });
            @endif

            // --- DataTables tabella ordini
            @if(!$noPartnerContext)
            const dtParams = @json($queryBase);
            $.fn.dataTable.ext.errMode = 'none';
            $('#revenue-table').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                processing: true,
                serverSide: true,
                pagingType: 'simple',
                layout: {
                    topStart: '',
                    bottomEnd: 'pageLength',
                    bottomStart: '',
                    topEnd: { info: {}, paging: {} }
                },
                language: {
                    lengthMenu: 'ELEMENTI PER PAGINA _MENU_',
                    paginate: { previous: '<', next: '>' },
                    info: '_START_-_END_ di _TOTAL_',
                    infoEmpty: '0-0 di 0',
                },
                ajax: {
                    url: '{{ route('statistics.revenue.data') }}',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                    },
                    data: function (d) { return Object.assign({}, dtParams, d); },
                },
                columns: [
                    { data: 'order_number' },
                    { data: 'customer', render: function (c) {
                        let html = $('<div>').text(c.name || '—').html();
                        if (c.phone) html += '<div class="text-muted small">' + $('<div>').text(c.phone).html() + '</div>';
                        return html;
                    }},
                    { data: 'visit_date', render: function (d) {
                        if (!d) return '—';
                        let html = $('<div>').text(d.date).html();
                        if (d.time) html += '<div class="text-muted small">' + d.time + '</div>';
                        return html;
                    }},
                    { data: 'product', render: function (p) {
                        let html = $('<div>').text(p.label || '—').html();
                        if (p.category) html += '<div class="text-muted small">' + $('<div>').text(p.category).html() + '</div>';
                        return html;
                    }},
                    { data: 'persons' },
                    { data: 'order_date', render: function (d) {
                        if (!d || !d.date) return '—';
                        return $('<div>').text(d.date).html() + (d.time ? '<div class="text-muted small">' + d.time + '</div>' : '');
                    }},
                    { data: 'customer_status', render: function (s) {
                        if (!s) return '—';
                        return '<div class="label-miticko" data-mode="labelAppearance-' + s.appearance + '"><span class="fa-regular fa-' + s.icon + ' me-1"></span>' + $('<div>').text(s.label).html() + '</div>';
                    }},
                    { data: 'order_status', render: function (s) {
                        if (!s) return '—';
                        return '<div class="label-miticko" data-mode="labelAppearance-' + s.appearance + '"><span class="fa-regular fa-' + s.icon + ' me-1"></span>' + $('<div>').text(s.label).html() + '</div>';
                    }},
                ],
            });
            @endif

            // --- Modale "Genera report"
            const $modal = $('#modal-generate-report');
            const $partner = $modal.find('select[name="report_partner_id"]');
            const $month   = $modal.find('select[name="report_month"]');
            const $year    = $modal.find('select[name="report_year"]');
            const $tbody   = $('#generate-report-subscribers-tbody');
            const $btnDownload = $modal.find('.btn-generate-report-download');
            const $btnSend     = $modal.find('.btn-generate-report-send');
            const switcheryOpts = { secondaryColor: '#cccccc', color: '#3B82F6' };

            function setButtonAppearance($btn, appearance) {
                const mode = ($btn.attr('data-mode') || '').replace(/buttonAppearance-\w+/, 'buttonAppearance-' + appearance);
                $btn.attr('data-mode', mode);
            }
            function updateActionButtons() {
                const periodOk = Boolean($partner.val() && $month.val() && $year.val());
                const sendToggleOn = $modal.find('input[name="report_send_master_toggle"]').is(':checked');
                $btnDownload.prop('disabled', !periodOk);
                setButtonAppearance($btnDownload, periodOk ? 'Primary' : 'Disabled');
                $btnSend.prop('disabled', !(periodOk && sendToggleOn));
                setButtonAppearance($btnSend, (periodOk && sendToggleOn) ? 'Primary' : 'Disabled');
                $('#generate-report-subscribers-section').toggleClass('locked', !sendToggleOn);
            }
            function initSwitchery(container) {
                const SwitcheryLib = window.Switchery;
                if (typeof SwitcheryLib !== 'function') return;
                document.querySelectorAll(container + ' .js-switch').forEach(function (el) {
                    if (el.getAttribute('data-switchery') === 'true') return;
                    new SwitcheryLib(el, switcheryOpts);
                });
            }
            function loadSubscribers(partnerId) {
                if (!partnerId) {
                    $tbody.html('<tr><td colspan="3" class="text-center text-muted py-4">Seleziona un partner per vedere i destinatari configurati.</td></tr>');
                    return;
                }
                $tbody.html('<tr><td colspan="3" class="text-center text-muted py-4">Caricamento in corso…</td></tr>');
                const url = '{{ url('partners') }}/' + partnerId + '/report/data/subscribers-partner';
                $.ajax({ url: url, method: 'POST', data: { _token: '{{ csrf_token() }}', length: 10, start: 0, draw: 1 } })
                    .done(function (res) {
                        const rows = (res && res.data) || [];
                        if (!rows.length) {
                            $tbody.html('<tr><td colspan="3" class="text-center text-muted py-4">Nessun destinatario configurato per questo partner.</td></tr>');
                            return;
                        }
                        const html = rows.map(function (r) {
                            return '<tr><td>' + (r.email || '—') + '</td><td>' + (r.role || '—') + '</td><td class="text-end">' + (r.toggle || '') + '</td></tr>';
                        }).join('');
                        $tbody.html(html);
                        initSwitchery('#generate-report-subscribers-tbody');
                    })
                    .fail(function () { $tbody.html('<tr><td colspan="3" class="text-center text-danger py-4">Errore nel caricamento dei destinatari.</td></tr>'); });
            }

            $(document).on('click', '#btn-open-generate-report', function () { $modal.modal('show'); });
            $modal.on('shown.bs.modal', function () {
                setTimeout(function () { initSwitchery('#modal-generate-report #form-generate-report'); }, 50);
                loadSubscribers($partner.val());
                updateActionButtons();
            });
            $partner.on('change', function () { loadSubscribers($(this).val()); updateActionButtons(); });
            $month.on('change', updateActionButtons);
            $year.on('change', updateActionButtons);
            $modal.on('change', 'input[name="report_send_master_toggle"]', updateActionButtons);
            $btnDownload.on('click', function (e) {
                e.preventDefault();
                if ($(this).prop('disabled')) return;
                const url = '{{ url('partners') }}/' + $partner.val() + '/report/pdf?year=' + $year.val() + '&month=' + $month.val();
                window.open(url, '_blank');
            });
            $btnSend.on('click', function (e) {
                e.preventDefault();
                if ($(this).prop('disabled')) return;
                if (typeof toastr !== 'undefined') { toastr.info('Invio report via email non ancora disponibile.'); }
                else { alert('Invio report via email non ancora disponibile.'); }
            });

            @if($isMitickoAdmin)
            $(document).on('change', 'select[name="statistics_partner_switcher"]', function () {
                const pid = $(this).val();
                const url = new URL(window.location.href);
                if (pid) { url.searchParams.set('partner_id', pid); } else { url.searchParams.delete('partner_id'); }
                window.location.href = url.toString();
            });
            @endif
        });
    </script>
@endsection
