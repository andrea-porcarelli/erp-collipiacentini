@php
    use Illuminate\Support\Facades\Storage;

    $brand = $partner?->brand ?? config('design.default_brand', 'miticko');
    $t = config("design.brands.{$brand}.tokens")
        ?? config('design.brands.miticko.tokens', []);

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

    $totals = $report['totals'];
    $period = $report['period'];
    $rows = $report['rows'];
    $billing = $partner->billing;

    $partnerLogoMedia = $partner->logo ?? null;
    $partnerLogoSrc = null;
    if ($partnerLogoMedia && Storage::disk('public')->exists($partnerLogoMedia->file_path)) {
        $logoPath = Storage::disk('public')->path($partnerLogoMedia->file_path);
        $logoMime = $partnerLogoMedia->file_type ?: 'image/png';
        $partnerLogoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }

    $footerLogoPath = public_path('assets/images/logo-miticko.png');
    $footerLogoSrc = file_exists($footerLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerLogoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report commissioni {{ $partner->partner_name }} — {{ $period['label'] }}</title>
    <style>
        @page { margin: 20mm 15mm 25mm 15mm; }

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
            color: {{ $t['text-main'] ?? '#111' }};
            font-size: 10.5px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .header-band {
            position: fixed;
            top: -15mm;
            left: -15mm;
            right: -15mm;
            height: 10mm;
            background: {{ $t['brand-primary-brand'] ?? '#2F6BFF' }};
        }

        .doc-header {
            width: 100%;
            margin-bottom: 22px;
        }
        .doc-header td { vertical-align: top; }
        .doc-header .partner-name {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
        }
        .doc-header .partner-meta {
            font-size: 10px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            margin-top: 3px;
        }
        .doc-header .right {
            text-align: right;
        }
        .doc-header .doc-title {
            font-size: 11px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
        .doc-header .doc-period {
            font-size: 18px;
            font-weight: 700;
            margin-top: 4px;
        }
        .doc-header .logo img { height: 22px; }

        .billing-box {
            border: 1px solid {{ $t['border-default'] ?? '#e0e0e0' }};
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 18px;
            font-size: 10px;
        }
        .billing-box .label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            font-size: 9px;
            margin-bottom: 4px;
        }

        .kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 6px;
            margin-bottom: 18px;
        }
        .kpi-grid td {
            width: 25%;
            padding: 10px 12px;
            background: #f6f6f7;
            border-radius: 6px;
            vertical-align: top;
        }
        .kpi-grid .kpi-label {
            font-size: 9px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }
        .kpi-grid .kpi-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 3px;
        }
        .kpi-grid .kpi-note {
            font-size: 9px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            margin-top: 3px;
        }
        .kpi-grid .kpi-warn { background: #fff3e0; }
        .kpi-grid .kpi-net { background: #e8f5e9; }
        .kpi-grid .kpi-info { background: #e3f2fd; }

        h2 {
            font-size: 12px;
            font-weight: 700;
            margin: 20px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: {{ $t['text-main'] ?? '#111' }};
        }

        table.orders {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        table.orders thead th {
            background: {{ $t['brand-primary-brand'] ?? '#2F6BFF' }};
            color: #fff;
            padding: 6px 5px;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
        }
        table.orders thead th.text-end,
        table.orders tbody td.text-end,
        table.orders tfoot td.text-end {
            text-align: right;
        }
        table.orders tbody td {
            border-bottom: 1px solid #eee;
            padding: 5px 5px;
            vertical-align: top;
        }
        table.orders tfoot td {
            border-top: 2px solid {{ $t['text-main'] ?? '#111' }};
            padding: 7px 5px;
            font-weight: 700;
            font-size: 10px;
        }
        table.orders .muted {
            color: {{ $t['text-secondary'] ?? '#666' }};
            font-size: 8.5px;
        }

        .empty {
            text-align: center;
            padding: 40px 10px;
            color: {{ $t['text-secondary'] ?? '#666' }};
            border: 1px dashed {{ $t['border-default'] ?? '#e0e0e0' }};
            border-radius: 8px;
        }

        .footer {
            position: fixed;
            bottom: -20mm;
            left: -15mm;
            right: -15mm;
            padding: 8px 15mm;
            background: #f6f6f7;
            font-size: 8.5px;
            color: {{ $t['text-secondary'] ?? '#666' }};
        }
        .footer td { vertical-align: middle; padding: 6px 0; }
        .footer .logo img { height: 16px; }
    </style>
</head>
<body>

<div class="header-band">&nbsp;</div>

<div class="footer">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 130px;" class="logo">
                @if($footerLogoSrc)
                    <img src="{{ $footerLogoSrc }}" alt="Miticko">
                @else
                    <span style="font-weight: 800; font-size: 12px;">miticko</span>
                @endif
            </td>
            <td>
                Report generato il {{ now()->translatedFormat('d/m/Y H:i') }} · Documento non fiscale, riepilogo interno delle commissioni maturate.
            </td>
        </tr>
    </table>
</div>

<table class="doc-header">
    <tr>
        <td style="width: 55%;">
            <div class="partner-name">{{ $partner->partner_name }}</div>
            <div class="partner-meta">
                Codice partner: <strong>{{ $partner->partner_code ?? '—' }}</strong>
                @if($partner->domain_name) · {{ $partner->domain_name }} @endif
            </div>
        </td>
        <td class="right" style="width: 45%;">
            <div class="doc-title">Report commissioni</div>
            <div class="doc-period">{{ $period['label'] }}</div>
            <div class="partner-meta">
                Periodo dal {{ $period['from']->format('d/m/Y') }} al {{ $period['to']->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

@if($billing)
    <div class="billing-box">
        <div class="label">Dati di fatturazione</div>
        <strong>{{ $billing->legal_name ?: $partner->partner_name }}</strong>
        @if($billing->vat_number) · P.IVA {{ $billing->vat_number }} @endif
        @if($billing->tax_code) · CF {{ $billing->tax_code }} @endif
        <br>
        {{ trim(($billing->street_address ?? '') . ' — ' . ($billing->postal_code ?? '') . ' ' . ($billing->city ?? '') . ' ' . ($billing->province ? '(' . $billing->province . ')' : ''), ' —') }}
        @if($billing->pec_email) <br>PEC: {{ $billing->pec_email }} @endif
        @if($billing->sdi_code) · SDI: {{ $billing->sdi_code }} @endif
    </div>
@endif

<h2>Riepilogo</h2>

<table class="kpi-grid">
    <tr>
        <td>
            <div class="kpi-label">Ordini pagati</div>
            <div class="kpi-value">{{ number_format($report['orders_count'], 0, ',', '.') }}</div>
            <div class="kpi-note">{{ $report['items_count'] }} biglietti</div>
        </td>
        <td>
            <div class="kpi-label">Lordo biglietti</div>
            <div class="kpi-value">{{ $fmtEuro($totals['gross']) }}</div>
            <div class="kpi-note">Prima delle commissioni</div>
        </td>
        <td class="kpi-warn">
            <div class="kpi-label">Commissioni Miticko</div>
            <div class="kpi-value">{{ $fmtEuro($totals['miticko_total']) }}</div>
            <div class="kpi-note">Fissa {{ $fmtEuro($totals['miticko_fixed']) }} · Var. {{ $fmtEuro($totals['miticko_variable']) }}</div>
        </td>
        <td class="kpi-warn">
            <div class="kpi-label">Commissioni bancarie</div>
            <div class="kpi-value">{{ $fmtEuro($totals['bank']) }}</div>
            <div class="kpi-note">A carico del partner</div>
        </td>
    </tr>
    <tr>
        <td class="kpi-net">
            <div class="kpi-label">Netto partner</div>
            <div class="kpi-value">{{ $fmtEuro($totals['partner_net']) }}</div>
            <div class="kpi-note">Lordo − commissioni Miticko/banca</div>
        </td>
        <td class="kpi-info">
            <div class="kpi-label">Prevendita (Miticko)</div>
            <div class="kpi-value">{{ $fmtEuro($totals['presale']) }}</div>
            <div class="kpi-note">Incassata dal cliente</div>
        </td>
        <td colspan="2">
            <div class="kpi-label">Note</div>
            <div class="kpi-note" style="margin-top: 4px; font-size: 9.5px;">
                Il netto partner è calcolato come lordo biglietti diminuito delle commissioni Miticko e bancarie.
                La prevendita è a carico del cliente ed è ricavo Miticko.
            </div>
        </td>
    </tr>
</table>

<h2>Prenotazioni del periodo</h2>

@if(empty($rows))
    <div class="empty">Nessun ordine pagato in {{ $period['label'] }}.</div>
@else
    <table class="orders">
        <thead>
        <tr>
            <th style="width: 11%;">Ordine</th>
            <th style="width: 13%;">Pagato il</th>
            <th>Cliente</th>
            <th class="text-end" style="width: 6%;">Bigl.</th>
            <th class="text-end" style="width: 11%;">Lordo</th>
            <th class="text-end" style="width: 11%;">Miticko</th>
            <th class="text-end" style="width: 10%;">Bancarie</th>
            <th class="text-end" style="width: 10%;">Prevendita</th>
            <th class="text-end" style="width: 11%;">Netto</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            @php($o = $row['order'])
            <tr>
                <td>MTK-{{ $o->order_number }}</td>
                <td>{{ $o->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                    {{ $o->full_name ?: '—' }}
                    @if($o->email)<br><span class="muted">{{ $o->email }}</span>@endif
                </td>
                <td class="text-end">{{ $row['items_count'] }}</td>
                <td class="text-end">{{ $fmtEuro($row['gross']) }}</td>
                <td class="text-end">{{ $fmtEuro($row['miticko']) }}</td>
                <td class="text-end">{{ $fmtEuro($row['bank']) }}</td>
                <td class="text-end">{{ $fmtEuro($row['presale']) }}</td>
                <td class="text-end"><strong>{{ $fmtEuro($row['net']) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
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
@endif

</body>
</html>
