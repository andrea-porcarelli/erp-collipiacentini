@php
    use Illuminate\Support\Facades\Storage;
    use App\Support\QuickChart;

    $brand = $partner?->brand ?? config('design.default_brand', 'miticko');
    $t = config("design.brands.{$brand}.tokens")
        ?? config('design.brands.miticko.tokens', []);

    $accent   = $t['brand-primary-brand'] ?? '#ab9038';
    $textMain = $t['text-main'] ?? '#111';
    $textMuted = $t['text-secondary'] ?? '#7a7a7a';
    $cardBg   = '#f6f1e3';
    $tableHeaderBg = '#f4efe4';

    $fontFamily = 'DM Sans';
    $fontFaces = [];
    foreach ([300, 400, 500, 700] as $w) {
        $slug = str_replace(' ', '', $fontFamily);
        $path = storage_path("fonts/{$slug}-{$w}.ttf");
        if (is_file($path)) {
            $fontFaces[] = [
                'family' => $fontFamily,
                'weight' => $w,
                'src'    => 'file://' . $path,
            ];
        }
    }

    $fmtEuro = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $fmtInt  = fn ($v) => number_format((int) $v, 0, ',', '.');

    $period = $report['period'];
    $previousPeriodLabel = $report['previous_period_label'] ?? null;
    $kpis = $report['kpis'];
    $productRows = $report['product_rows'] ?? [];
    $trend = $report['monthly_trend'] ?? [];
    $distribution = $report['product_distribution'] ?? [];
    $totals = $report['totals'];

    // Carica logo partner inline (base64) per PDF self-contained
    $partnerLogoMedia = $partner->logo ?? null;
    $partnerLogoSrc = null;
    if ($partnerLogoMedia && Storage::disk('public')->exists($partnerLogoMedia->file_path)) {
        $logoPath = Storage::disk('public')->path($partnerLogoMedia->file_path);
        $logoMime = $partnerLogoMedia->file_type ?: 'image/png';
        $partnerLogoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }

    // Logo Miticko (header + footer)
    $mitickoLogoPath = public_path('assets/images/logo-miticko.png');
    $mitickoLogoSrc = file_exists($mitickoLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($mitickoLogoPath))
        : null;

    // Grafici: scarico come data URI per PDF self-contained
    $trendLabels = array_map(fn ($r) => $r['label'], $trend);
    $trendValues = array_map(fn ($r) => $r['units'], $trend);
    $barChartUrl = $trend ? QuickChart::fetchDataUri(QuickChart::bar($trendLabels, $trendValues, $accent)) : null;

    $distLabels = array_map(fn ($r) => $r['label'], $distribution);
    $distValues = array_map(fn ($r) => $r['units'], $distribution);
    $distColors = QuickChart::defaultPalette(count($distLabels), [$accent, $accent.'cc', $accent.'99', $accent.'66', '#5d4e22', '#c1a85a']);
    $donutChartUrl = $distribution ? QuickChart::fetchDataUri(QuickChart::doughnut($distLabels, $distValues, $distColors)) : null;

    // KPI helpers
    $kpiLabels = [
        'orders'     => 'Ordini',
        'items'      => 'Esperienze vendute',
        'revenue'    => 'Fatturato',
        'net_margin' => 'Margine netto',
    ];
    $isEuro = ['revenue' => true, 'net_margin' => true, 'orders' => false, 'items' => false];
    $formatKpi = fn ($key, $v) => $isEuro[$key] ? $fmtEuro($v) : $fmtInt($v);
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report mensile — {{ $partner->partner_name }} — {{ $period['label'] }}</title>
    <style>
        @page { margin: 14mm 10mm 20mm 10mm; }

        @foreach($fontFaces as $face)
        @font-face {
            font-family: "{{ $face['family'] }}";
            font-style: normal;
            font-weight: {{ $face['weight'] }};
            src: url({{ $face['src'] }}) format("truetype");
        }
        @endforeach

        * { box-sizing: border-box; }

        body {
            font-family: "{{ $fontFamily }}", DejaVu Sans, sans-serif;
            color: {{ $textMain }};
            font-size: 9px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 14px;
        }
        .header-logo img { height: 22px; }
        .header-logo .fallback { font-weight: 700; font-size: 14px; }

        .doc-head {
            width: 100%;
            margin-bottom: 10px;
        }
        .doc-head td { vertical-align: top; }
        .doc-head .partner-eyebrow {
            font-size: 10px;
            color: {{ $textMain }};
            font-weight: 500;
            margin-bottom: 2px;
        }
        .doc-head .doc-title {
            font-size: 20px;
            font-weight: 700;
            color: {{ $textMain }};
            margin: 0;
            line-height: 1.15;
        }
        .doc-head .doc-sub {
            font-size: 9px;
            color: {{ $textMuted }};
            margin-top: 2px;
        }
        .doc-head .partner-logo {
            text-align: right;
        }
        .doc-head .partner-logo img { max-height: 46px; max-width: 150px; }

        .kpi-grid {
            width: 100%;
            margin-top: 10px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-cell {
            background: {{ $cardBg }};
            padding: 10px 12px;
            border-radius: 10px;
            vertical-align: top;
            width: 25%;
        }
        .kpi-icon {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            background: #ffffff;
            display: inline-block;
            text-align: center;
            line-height: 20px;
            font-weight: 700;
            color: {{ $accent }};
            margin-bottom: 6px;
            font-size: 10px;
        }
        .kpi-label {
            font-size: 8px;
            color: {{ $textMuted }};
            text-transform: none;
            font-weight: 500;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 700;
            color: {{ $textMain }};
            line-height: 1.1;
            margin-bottom: 4px;
        }
        .kpi-delta {
            font-size: 7.5px;
            color: {{ $textMuted }};
        }
        .kpi-delta .delta-up    { color: #2a9d4a; font-weight: 600; }
        .kpi-delta .delta-down  { color: #d14343; font-weight: 600; }
        .kpi-delta .delta-flat  { color: {{ $textMuted }}; }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin: 18px 0 8px;
            color: {{ $textMain }};
        }

        .table-wrap { background: #fff; border-radius: 10px; overflow: hidden; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        table.data thead th {
            background: {{ $tableHeaderBg }};
            color: {{ $textMain }};
            font-weight: 500;
            text-align: left;
            padding: 8px 10px;
            border-bottom: none;
            font-size: 8px;
        }
        table.data tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #ececec;
            color: {{ $textMain }};
        }
        table.data tbody tr:last-child td { border-bottom: none; }
        table.data tfoot td {
            padding: 10px;
            font-weight: 700;
            border-top: 1px solid #d9d9d9;
            background: #ffffff;
        }
        .num { text-align: right; white-space: nowrap; }

        .charts {
            width: 100%;
            margin-top: 18px;
        }
        .charts td { vertical-align: top; padding-right: 10px; }
        .chart-title {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 6px;
            color: {{ $textMain }};
        }
        .chart-box { background: transparent; }
        .chart-box img { width: 100%; max-width: 100%; height: auto; }

        .donut-legend {
            font-size: 8px;
            color: {{ $textMain }};
            margin-top: 6px;
        }
        .donut-legend .dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .donut-legend .legend-row { margin-bottom: 3px; }

        .footer {
            position: fixed;
            bottom: -16mm;
            left: -10mm;
            right: -10mm;
            padding: 8px 10mm;
            background: #ffffff;
            border-top: 1px solid #ececec;
            font-size: 7px;
            color: {{ $textMuted }};
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { vertical-align: middle; padding: 2px 0; }
        .footer .logo img { height: 12px; }
        .footer .logo .fallback { font-weight: 700; font-size: 10px; color: {{ $textMain }}; }
    </style>
</head>
<body>

<div class="footer">
    <table>
        <tr>
            <td style="width: 90px;" class="logo">
                @if($mitickoLogoSrc)
                    <img src="{{ $mitickoLogoSrc }}" alt="Miticko">
                @else
                    <span class="fallback">miticko</span>
                @endif
            </td>
            <td>
                Servizio offerto da Miticko (miticko.com) · Miticko.com è un brand di Colli Italiani S.N.C.<br>
                P.IVA 12341080983 · San Giuliano Milanese (MI) Via Fratelli Rizzi 8 · CAP 20098 · Italia
            </td>
        </tr>
    </table>
</div>

<div class="header-logo">
    @if($mitickoLogoSrc)
        <img src="{{ $mitickoLogoSrc }}" alt="Miticko">
    @else
        <span class="fallback">miticko</span>
    @endif
</div>

<table class="doc-head">
    <tr>
        <td style="width: 70%;">
            <div class="partner-eyebrow">{{ $partner->partner_name }}</div>
            <h1 class="doc-title">Report mensile · {{ ucfirst($period['label']) }}</h1>
            <div class="doc-sub">vendite e rendicontazione mensile</div>
        </td>
        <td style="width: 30%;" class="partner-logo">
            @if($partnerLogoSrc)
                <img src="{{ $partnerLogoSrc }}" alt="{{ $partner->partner_name }}">
            @endif
        </td>
    </tr>
</table>

<table class="kpi-grid">
    <tr>
        @foreach(['orders', 'items', 'revenue', 'net_margin'] as $key)
            @php
                $kpi = $kpis[$key];
                $direction = $kpi['direction'];
                $deltaPct = $kpi['delta_pct'];
                $arrow = $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→');
                $deltaClass = 'delta-' . $direction;
            @endphp
            <td class="kpi-cell">
                <div class="kpi-icon">⬡</div>
                <div class="kpi-label">{{ $kpiLabels[$key] }}</div>
                <div class="kpi-value">{{ $formatKpi($key, $kpi['current']) }}</div>
                <div class="kpi-delta">
                    @if($deltaPct !== null)
                        <span class="{{ $deltaClass }}">{{ $arrow }} {{ ($deltaPct > 0 ? '+' : '') . number_format($deltaPct, 0, ',', '.') }}%</span>
                        rispetto {{ ucfirst($previousPeriodLabel) }}
                    @else
                        — nessun dato {{ ucfirst($previousPeriodLabel) }}
                    @endif
                </div>
            </td>
        @endforeach
    </tr>
</table>

<div class="section-title">Dettaglio vendite per prodotto</div>

<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Prodotto</th>
                <th>Variante</th>
                <th class="num">Prezzo<br>singolo<br>prodotto</th>
                <th class="num">Commissione<br>singolo<br>prodotto</th>
                <th class="num">Margine<br>singolo<br>prodotto</th>
                <th class="num">Unità<br>vendute</th>
                <th class="num">Totale<br>vendite</th>
                <th class="num">Totale<br>commissioni</th>
                <th class="num">Totale incassato<br>dal partner</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productRows as $row)
                <tr>
                    <td>{{ $row['product'] }}</td>
                    <td>{{ $row['variant'] }}</td>
                    <td class="num">{{ $fmtEuro($row['unit_price']) }}</td>
                    <td class="num">{{ $fmtEuro($row['commission_unit']) }}</td>
                    <td class="num">{{ $fmtEuro($row['margin_unit']) }}</td>
                    <td class="num">{{ $fmtInt($row['units']) }}</td>
                    <td class="num">{{ $fmtEuro($row['total_gross']) }}</td>
                    <td class="num">{{ $fmtEuro($row['total_commissions']) }}</td>
                    <td class="num">{{ $fmtEuro($row['total_partner']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 24px; color: {{ $textMuted }};">
                        Nessuna vendita registrata nel periodo.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($productRows))
            <tfoot>
                <tr>
                    <td colspan="5">Totale periodo</td>
                    <td class="num">{{ $fmtInt($report['items_count']) }}</td>
                    <td class="num">{{ $fmtEuro($totals['gross']) }}</td>
                    <td class="num">{{ $fmtEuro($totals['miticko_total']) }}</td>
                    <td class="num">{{ $fmtEuro($totals['partner_net']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<table class="charts">
    <tr>
        <td style="width: 50%;">
            <div class="chart-title">Andamento vendite</div>
            @if($barChartUrl)
                <div class="chart-box"><img src="{{ $barChartUrl }}" alt="Andamento vendite"></div>
            @else
                <div class="chart-box" style="padding:40px; text-align:center; color:{{ $textMuted }};">Nessun dato</div>
            @endif
        </td>
        <td style="width: 50%;">
            <div class="chart-title">Distribuzione vendite</div>
            @if($donutChartUrl)
                <table style="width:100%;">
                    <tr>
                        <td style="width:55%;"><img src="{{ $donutChartUrl }}" alt="Distribuzione vendite" style="width:100%;"></td>
                        <td class="donut-legend">
                            @foreach($distribution as $i => $d)
                                <div class="legend-row">
                                    <span class="dot" style="background: {{ $distColors[$i] ?? $accent }};"></span>
                                    {{ $d['label'] }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                </table>
            @else
                <div class="chart-box" style="padding:40px; text-align:center; color:{{ $textMuted }};">Nessun dato</div>
            @endif
        </td>
    </tr>
</table>

</body>
</html>
