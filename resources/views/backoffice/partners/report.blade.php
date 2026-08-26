@extends('backoffice.layout', ['title' => 'Report commissioni partner', 'active' => $path])

@php
    use App\Enums\OrderStatus;

    $months = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio',  6 => 'Giugno',   7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    $currentYear = (int) now()->year;
    $years = range($currentYear + 1, $currentYear - 5);

    $monthOptions = collect($months)->map(fn ($label, $id) => ['id' => $id, 'label' => $label])->values()->all();
    $yearOptions = collect($years)->map(fn ($y) => ['id' => $y, 'label' => (string) $y])->all();

    $fmtEuro = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';

    $totals = $report['totals'];
    $period = $report['period'];
    $rows = $report['rows'];
@endphp

@section('main-content')
    <div class="d-flex justify-content-between top-bar-page">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button :href="route('partners.index')" class="btn-success" emphasis="outlined" leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Partner', 'partners.index']" :second="['Report commissioni · ' . $partner->partner_name]" />
                <x-header-page :title="'Report commissioni · ' . $partner->partner_name" />
            </div>
        </div>
    </div>

    <div class="w-100 mt-spacing-2xl">
        <div class="row g-3">
            {{-- Selettore periodo --}}
            <div class="col-12">
                <x-card title="Periodo di riferimento" sub_title="Seleziona mese e anno per aggregare le commissioni maturate">
                    <form method="GET" action="{{ route('partners.report', $partner->id) }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <x-select name="month" label="Mese" :value="$selectedMonth" :options="$monthOptions" />
                        </div>
                        <div class="col-md-3">
                            <x-select name="year" label="Anno" :value="$selectedYear" :options="$yearOptions" />
                        </div>
                        <div class="col-md-6 d-flex gap-2 justify-content-end">
                            <x-button type="submit" label="Aggiorna" leading="fa-arrows-rotate" status="Primary" emphasis="Medium" />
                            <x-button
                                :href="route('partners.report.pdf', ['partner' => $partner->id, 'year' => $selectedYear, 'month' => $selectedMonth])"
                                label="Scarica PDF"
                                leading="fa-file-pdf"
                                status="Success"
                                emphasis="Medium"
                                :disabled="$report['orders_count'] === 0"
                            />
                        </div>
                    </form>

                    @if(!empty($availablePeriods))
                        <div class="mt-3 small text-secondary">
                            <strong>Periodi con prenotazioni pagate:</strong>
                            @foreach($availablePeriods as $ap)
                                <a class="me-2" href="{{ route('partners.report', ['partner' => $partner->id, 'year' => $ap['year'], 'month' => $ap['month']]) }}">{{ $ap['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Riepilogo KPI --}}
            <div class="col-12">
                <x-card :title="'Riepilogo · ' . $period['label']" sub_title="Aggregati calcolati sugli ordini pagati nel periodo">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: var(--surface-container-low, #f6f6f7);">
                                <div class="text-secondary small">Ordini</div>
                                <div class="fs-4 fw-bold">{{ number_format($report['orders_count'], 0, ',', '.') }}</div>
                                <div class="text-secondary small">{{ $report['items_count'] }} biglietti</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: var(--surface-container-low, #f6f6f7);">
                                <div class="text-secondary small">Lordo biglietti</div>
                                <div class="fs-4 fw-bold">{{ $fmtEuro($totals['gross']) }}</div>
                                <div class="text-secondary small">Prima delle commissioni</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: #fff3e0;">
                                <div class="text-secondary small">Commissioni Miticko</div>
                                <div class="fs-4 fw-bold">{{ $fmtEuro($totals['miticko_total']) }}</div>
                                <div class="text-secondary small">
                                    Fissa {{ $fmtEuro($totals['miticko_fixed']) }} · Var. {{ $fmtEuro($totals['miticko_variable']) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: #fff3e0;">
                                <div class="text-secondary small">Commissioni bancarie</div>
                                <div class="fs-4 fw-bold">{{ $fmtEuro($totals['bank']) }}</div>
                                <div class="text-secondary small">A carico del partner</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: #e8f5e9;">
                                <div class="text-secondary small">Netto partner</div>
                                <div class="fs-4 fw-bold">{{ $fmtEuro($totals['partner_net']) }}</div>
                                <div class="text-secondary small">Lordo − commissioni</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 rounded" style="background: #e3f2fd;">
                                <div class="text-secondary small">Prevendita (Miticko)</div>
                                <div class="fs-4 fw-bold">{{ $fmtEuro($totals['presale']) }}</div>
                                <div class="text-secondary small">Incassata dal cliente</div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Tabella ordini --}}
            <div class="col-12">
                <x-card title="Prenotazioni del periodo" sub_title="Dettaglio ordine per ordine">
                    @if(empty($rows))
                        <div class="text-center py-5 text-secondary">
                            <i class="fa-regular fa-inbox fa-2x mb-2"></i>
                            <div>Nessun ordine pagato in {{ $period['label'] }}.</div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table-miticko w-100">
                                <thead>
                                <tr>
                                    <th style="width: 12%;">Ordine</th>
                                    <th style="width: 14%;">Pagato il</th>
                                    <th>Cliente</th>
                                    <th class="text-end" style="width: 8%;">Biglietti</th>
                                    <th class="text-end" style="width: 11%;">Lordo</th>
                                    <th class="text-end" style="width: 11%;">Miticko</th>
                                    <th class="text-end" style="width: 11%;">Bancarie</th>
                                    <th class="text-end" style="width: 11%;">Prevendita</th>
                                    <th class="text-end" style="width: 12%;">Netto partner</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    @php($o = $row['order'])
                                    <tr>
                                        <td>
                                            <a href="{{ route('orders.show', $o->id) }}">MTK-{{ $o->order_number }}</a>
                                        </td>
                                        <td>{{ $o->paid_at?->translatedFormat('d/m/Y H:i') ?? '—' }}</td>
                                        <td>{{ $o->full_name ?: '—' }}<br><span class="text-secondary small">{{ $o->email }}</span></td>
                                        <td class="text-end">{{ $row['items_count'] }}</td>
                                        <td class="text-end">{{ $fmtEuro($row['gross']) }}</td>
                                        <td class="text-end">{{ $fmtEuro($row['miticko']) }}</td>
                                        <td class="text-end">{{ $fmtEuro($row['bank']) }}</td>
                                        <td class="text-end">{{ $fmtEuro($row['presale']) }}</td>
                                        <td class="text-end fw-bold">{{ $fmtEuro($row['net']) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totali</td>
                                    <td class="text-end">{{ $report['items_count'] }}</td>
                                    <td class="text-end">{{ $fmtEuro($totals['gross']) }}</td>
                                    <td class="text-end">{{ $fmtEuro($totals['miticko_total']) }}</td>
                                    <td class="text-end">{{ $fmtEuro($totals['bank']) }}</td>
                                    <td class="text-end">{{ $fmtEuro($totals['presale']) }}</td>
                                    <td class="text-end">{{ $fmtEuro($totals['partner_net']) }}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
@endsection
