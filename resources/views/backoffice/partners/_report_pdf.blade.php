@php
    use Illuminate\Support\Facades\Storage;
    use App\Support\QuickChart;

    $brand = $partner?->brand ?? config('design.default_brand', 'miticko');
    $t = config("design.brands.{$brand}.tokens")
        ?? config('design.brands.miticko.tokens', []);

    $accent    = $t['brand-primary-brand'] ?? '#ab9038';
    $textMain  = '#1a1a1a';
    $textMuted = '#8a857a';
    $pageBg    = '#f4f0e4';
    $cardBg    = '#ebe3cf';
    $tableHeaderBg = '#f6f1e3';
    $tableFooterBg = '#f1ebd8';
    $borderSoft = '#eceae4';

    $fontFamily = 'DM Sans';
    $fontFaces = [];
    foreach ([300, 400, 500, 700] as $w) {
        $slug = str_replace(' ', '', $fontFamily);
        $path = storage_path("fonts/{$slug}-{$w}.ttf");
        if (is_file($path)) {
            $fontFaces[] = ['family' => $fontFamily, 'weight' => $w, 'src' => 'file://' . $path];
        }
    }

    $fmtEuro = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $fmtInt  = fn ($v) => number_format((int) $v, 0, ',', '.');
    $fmtEuroCompact = function ($v) {
        $n = (float) $v;
        return (abs($n - round($n)) < 0.01)
            ? number_format($n, 0, ',', '.') . '€'
            : number_format($n, 2, ',', '.') . '€';
    };

    $period = $report['period'];
    $previousPeriodLabel = $report['previous_period_label'] ?? null;
    $kpis = $report['kpis'];
    $productRows = $report['product_rows'] ?? [];
    $trend = $report['monthly_trend'] ?? [];
    $distribution = $report['product_distribution'] ?? [];
    $totals = $report['totals'];

    $partnerLogoMedia = $partner->logo ?? null;
    $partnerLogoSrc = null;
    if ($partnerLogoMedia && Storage::disk('public')->exists($partnerLogoMedia->file_path)) {
        $logoPath = Storage::disk('public')->path($partnerLogoMedia->file_path);
        $logoMime = $partnerLogoMedia->file_type ?: 'image/png';
        $partnerLogoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }

    $mitickoLogoPath = public_path('assets/images/logo-miticko.png');
    $mitickoLogoSrc = file_exists($mitickoLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($mitickoLogoPath))
        : null;

    $trendLabels = array_map(fn ($r) => ucfirst($r['label']), $trend);
    $trendValues = array_map(fn ($r) => $r['units'], $trend);
    $barChartUrl = $trend ? QuickChart::fetchDataUri(QuickChart::bar($trendLabels, $trendValues, $accent)) : null;

    $distLabels = array_map(fn ($r) => $r['label'], $distribution);
    $distValues = array_map(fn ($r) => $r['units'], $distribution);
    $distColors = QuickChart::defaultPalette(count($distLabels), [$accent, $accent.'cc', $accent.'99', $accent.'66', '#5d4e22', '#c1a85a']);
    $donutChartUrl = $distribution ? QuickChart::fetchDataUri(QuickChart::doughnut($distLabels, $distValues, $distColors)) : null;

    $kpiLabels = [
        'orders'     => 'Ordini',
        'items'      => 'Esperienze vendute',
        'revenue'    => 'Fatturato',
        'net_margin' => 'Margine netto',
    ];
    $isEuro = ['revenue' => true, 'net_margin' => true, 'orders' => false, 'items' => false];
    $formatKpi = fn ($key, $v) => $isEuro[$key] ? $fmtEuroCompact($v) : $fmtInt($v);

    // SVG cubo "box" per icona KPI (DomPDF supporta SVG inline)
    $cubeIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#1a1a1a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.3 7 12 12 20.7 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report mensile — {{ $partner->partner_name }} — {{ $period['label'] }}</title>
    <style>
        @page { margin: 0; }

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
            background: {{ $pageBg }};
        }

        .page-wrap { padding: 22px 24px 40px; }

        .brand-header { text-align: center; padding: 6px 0 18px; }
        .brand-header img { height: 24px; vertical-align: middle; }

        .content {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px 24px 28px;
        }

        .doc-head { width: 100%; margin-bottom: 16px; }
        .doc-head td { vertical-align: middle; }
        .partner-eyebrow { font-size: 10px; font-weight: 500; color: {{ $textMain }}; margin-bottom: 2px; }
        .doc-title { font-size: 20px; font-weight: 700; color: {{ $textMain }}; margin: 0; line-height: 1.15; }
        .doc-sub { font-size: 9px; color: {{ $textMuted }}; margin-top: 2px; }
        .partner-logo { text-align: right; }
        .partner-logo img { max-height: 50px; max-width: 160px; }

        .kpi-grid { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 4px; }
        .kpi-cell {
            background: {{ $cardBg }};
            padding: 12px 14px;
            border-radius: 10px;
            vertical-align: top;
            width: 25%;
        }
        .kpi-icon-box {
            display: inline-block;
            width: 34px;
            height: 34px;
            background: #ffffff;
            border-radius: 8px;
            text-align: center;
            vertical-align: middle;
            padding: 7px 0 0 0;
        }
        .kpi-label { font-size: 9px; font-weight: 500; color: {{ $textMain }}; margin-top: 10px; margin-bottom: 2px; }
        .kpi-value { font-size: 20px; font-weight: 700; color: {{ $textMain }}; line-height: 1.1; margin-bottom: 6px; }
        .kpi-delta { font-size: 8px; color: {{ $textMuted }}; }
        .kpi-delta .delta-up    { color: #2e8c47; font-weight: 600; }
        .kpi-delta .delta-down  { color: #c53b3b; font-weight: 600; }
        .kpi-delta .delta-flat  { color: {{ $textMuted }}; font-weight: 600; }
        .kpi-delta .arr { font-weight: 700; }

        .section-title { font-size: 13px; font-weight: 700; margin: 22px 0 10px; color: {{ $textMain }}; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        table.data thead th {
            background: {{ $tableHeaderBg }};
            color: {{ $textMuted }};
            font-weight: 500;
            text-align: left;
            padding: 10px 10px;
            font-size: 8px;
            line-height: 1.25;
            border-bottom: none;
            white-space: normal;
            vertical-align: top;
        }
        table.data tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid {{ $borderSoft }};
            color: {{ $textMain }};
            vertical-align: middle;
        }
        table.data tbody tr:last-child td { border-bottom: 1px solid {{ $borderSoft }}; }
        table.data tfoot td {
            padding: 12px 10px;
            font-weight: 700;
            background: {{ $tableFooterBg }};
            color: {{ $textMain }};
        }
        .num { text-align: right; white-space: nowrap; }

        .charts { width: 100%; margin-top: 22px; }
        .charts td { vertical-align: top; padding-right: 10px; }
        .chart-title { font-size: 11px; font-weight: 700; margin-bottom: 8px; color: {{ $textMain }}; }
        .chart-title .ic { color: {{ $accent }}; margin-right: 4px; }
        .chart-box img { width: 100%; max-width: 100%; height: auto; }

        .donut-legend { font-size: 8.5px; color: {{ $textMain }}; }
        .donut-legend .dot {
            display: inline-block;
            width: 7px; height: 7px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .donut-legend .legend-row { margin-bottom: 4px; }

        .page-footer {
            margin: 16px 24px 12px;
            padding: 10px 0 0;
            border-top: 1px solid {{ $borderSoft }};
            font-size: 7.5px;
            color: {{ $textMuted }};
            line-height: 1.5;
        }
        .page-footer table { width: 100%; border-collapse: collapse; }
        .page-footer td { vertical-align: middle; padding: 2px 0; }
        .page-footer .logo img { height: 12px; }
    </style>
</head>
<body>

<div class="page-wrap">

    <div class="brand-header">
        @if($mitickoLogoSrc)
            <img src="{{ $mitickoLogoSrc }}" alt="Miticko">
        @else
            <span style="font-weight:700;font-size:14px;">miticko</span>
        @endif
    </div>

    <div class="content">
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
                        <div class="kpi-icon-box">{!! $cubeIcon !!}</div>
                        <div class="kpi-label">{{ $kpiLabels[$key] }}</div>
                        <div class="kpi-value">{{ $formatKpi($key, $kpi['current']) }}</div>
                        <div class="kpi-delta">
                            @if($deltaPct !== null)
                                <span class="{{ $deltaClass }}"><span class="arr">{{ $arrow }}</span> {{ ($deltaPct > 0 ? '+' : '') . number_format($deltaPct, 0, ',', '.') }}%</span><br>
                                rispetto {{ ucfirst($previousPeriodLabel) }}
                            @else
                                <span class="delta-flat">—</span><br>
                                nessun dato {{ ucfirst($previousPeriodLabel) }}
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>

        <div class="section-title">Dettaglio vendite per prodotto</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:12%;">Prodotto</th>
                    <th style="width:8%;">Variante</th>
                    <th class="num" style="width:9%;">Prezzo<br>singolo<br>prodotto</th>
                    <th class="num" style="width:11%;">Commissione<br>singolo<br>prodotto</th>
                    <th class="num" style="width:9%;">Margine<br>singolo<br>prodotto</th>
                    <th class="num" style="width:7%;">Unità<br>vendute</th>
                    <th class="num" style="width:11%;">Totale<br>vendite</th>
                    <th class="num" style="width:11%;">Totale<br>Commissioni</th>
                    <th class="num" style="width:12%;">Totale incassato<br>dal partner</th>
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
                        <td colspan="9" style="text-align: center; padding: 28px; color: {{ $textMuted }};">
                            Nessuna vendita registrata nel periodo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(!empty($productRows))
                <tfoot>
                    <tr>
                        <td>Totale<br>periodo</td>
                        <td>-</td>
                        <td class="num">-</td>
                        <td class="num">-</td>
                        <td class="num">-</td>
                        <td class="num">{{ $fmtInt($report['items_count']) }}</td>
                        <td class="num">{{ $fmtEuro($totals['gross']) }}</td>
                        <td class="num">{{ $fmtEuro($totals['miticko_total']) }}</td>
                        <td class="num">{{ $fmtEuro($totals['partner_net']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <table class="charts">
            <tr>
                <td style="width: 50%;">
                    <div class="chart-title"><span class="ic">▁</span> Andamento vendite</div>
                    @if($barChartUrl)
                        <div class="chart-box"><img src="{{ $barChartUrl }}" alt="Andamento vendite"></div>
                    @else
                        <div style="padding:40px; text-align:center; color:{{ $textMuted }};">Nessun dato</div>
                    @endif
                </td>
                <td style="width: 50%;">
                    <div class="chart-title"><span class="ic">◉</span> Distribuzione vendite</div>
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
                        <div style="padding:40px; text-align:center; color:{{ $textMuted }};">Nessun dato</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="page-footer">
        <table>
            <tr>
                <td style="width: 90px;" class="logo">
                    @if($mitickoLogoSrc)
                        <img src="{{ $mitickoLogoSrc }}" alt="Miticko">
                    @else
                        <span style="font-weight:700;font-size:10px;">miticko</span>
                    @endif
                </td>
                <td>
                    Servizio offerto da Miticko (miticko.com) · Miticko.com è un brand di Colli Italiani S.N.C.<br>
                    P.IVA 12341080983 · San Giuliano Milanese (MI) Via Fratelli Rizzi 8 · CAP 20098 · Italia
                </td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
