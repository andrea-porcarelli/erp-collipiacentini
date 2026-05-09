<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ricevuta #{{ $order->order_number }}</title>
    <style>
        /* Miticko design tokens (light, PDF-safe) */
        @page { margin: 32px 36px 90px 36px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0D0D0D;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .receipt-header {
            width: 100%;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 2px solid #0D0D0D;
        }
        .receipt-header td { vertical-align: top; }
        .receipt-title {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.5px;
            color: #0D0D0D;
            margin: 0 0 6px;
        }
        .receipt-subtitle {
            font-size: 12px;
            color: #666;
            font-weight: 400;
        }
        .receipt-header .right { text-align: right; }
        .receipt-header .partner-name {
            font-size: 13px;
            font-weight: 700;
            color: #0D0D0D;
            margin-bottom: 2px;
        }
        .receipt-header .meta {
            color: #666;
            font-size: 11px;
            line-height: 1.7;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            background: #E8F5E9;
            color: #1B5E20;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.pending  { background: #FFF4E5; color: #B36100; }
        .badge.failed,
        .badge.cancelled { background: #FDECEA; color: #B71C1C; }
        .badge.refunded { background: #ECEFF1; color: #455A64; }

        /* Section */
        .section { margin-top: 28px; }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 12px;
            color: #0D0D0D;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* Card-like info grid */
        .info-grid {
            width: 100%;
            border: 1px solid #E6E6E6;
            border-radius: 12px;
            background: #FAFAFA;
            padding: 16px 20px;
            margin-top: 4px;
        }
        .info-grid td {
            vertical-align: top;
            padding: 6px 12px 6px 0;
            width: 33.33%;
        }
        .info-label {
            color: #666;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #0D0D0D;
        }

        /* Tickets table */
        table.tickets {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 4px;
            border: 1px solid #E6E6E6;
            border-radius: 12px;
            overflow: hidden;
        }
        table.tickets thead th {
            background: #F2F2F2;
            color: #666;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #E6E6E6;
        }
        table.tickets thead th.center { text-align: center; }
        table.tickets thead th.right  { text-align: right; }
        table.tickets tbody td {
            padding: 12px 14px;
            font-size: 12px;
            color: #0D0D0D;
            border-bottom: 1px solid #F2F2F2;
        }
        table.tickets tbody tr:last-child td { border-bottom: none; }
        table.tickets tbody td.center { text-align: center; }
        table.tickets tbody td.right  { text-align: right; font-variant-numeric: tabular-nums; }
        .ticket-name { font-weight: 700; }

        /* Totals box (right-aligned card) */
        .totals-wrap {
            width: 100%;
            margin-top: 16px;
        }
        .totals-wrap td { vertical-align: top; }
        .totals {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #E6E6E6;
            border-radius: 12px;
            background: #FAFAFA;
            padding: 6px 16px;
        }
        .totals td {
            padding: 8px 16px;
            font-size: 12px;
            border: none;
        }
        .totals td.right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .totals tr.total-line td {
            border-top: 2px solid #0D0D0D;
            font-weight: 700;
            font-size: 14px;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        /* Customer block */
        .customer {
            border: 1px solid #E6E6E6;
            border-radius: 12px;
            padding: 16px 20px;
            background: #FFF;
            line-height: 1.7;
        }
        .customer .name { font-weight: 700; font-size: 13px; color: #0D0D0D; }
        .customer .row  { color: #666; font-size: 12px; }

        /* Footer (fixed at bottom of every page) */
        .receipt-footer {
            position: fixed;
            left: 0; right: 0; bottom: -50px;
            padding: 16px 0 0;
            border-top: 1px solid #E6E6E6;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .receipt-footer .powered {
            display: inline-block;
            text-align: center;
        }
        .receipt-footer .powered .label {
            display: block;
            color: #999;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .receipt-footer .powered img {
            height: 18px;
            vertical-align: middle;
        }
        .receipt-footer .legal {
            margin-top: 6px;
            color: #B2B2B2;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @php($firstOp = $order->orderProducts->first())
    @php($effettuatoIl = $order->paid_at ?? $order->created_at)
    @php($statusKey = $order->order_status->value)
    @php($logoPath = public_path('assets/images/logo-miticko.png'))

    {{-- HEADER --}}
    <table class="receipt-header">
        <tr>
            <td style="width: 60%;">
                <div class="receipt-title">Ricevuta</div>
                <div class="receipt-subtitle">#{{ $order->order_number }}</div>
            </td>
            <td class="right" style="width: 40%;">
                @if($order->partner)
                    <div class="partner-name">{{ $order->partner->partner_name }}</div>
                @endif
                <div class="meta">
                    Effettuato il {{ $effettuatoIl?->translatedFormat('j F Y') }} alle {{ $effettuatoIl?->format('H:i') }}<br>
                    <span class="badge {{ $statusKey }}">{{ $order->order_status->label() }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- DETTAGLI ORDINE --}}
    <div class="section">
        <div class="section-title">Dettagli ordine</div>
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Numero ordine</div>
                    <div class="info-value">#{{ $order->order_number }}</div>
                </td>
                @if($firstOp)
                    <td>
                        <div class="info-label">Data e ora visita</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j F Y') }} · ore {{ substr($firstOp->booking_time, 0, 5) }}</div>
                    </td>
                    <td>
                        <div class="info-label">Prodotto</div>
                        <div class="info-value">{{ $firstOp->product?->label }}</div>
                    </td>
                @endif
            </tr>
            @if($order->card_brand || $order->stripe_payment_method)
                <tr>
                    <td>
                        <div class="info-label">Metodo di pagamento</div>
                        <div class="info-value">
                            @if($order->card_brand)
                                {{ ucfirst($order->card_brand) }}@if($order->card_last4) · {{ $order->card_last4 }}@endif
                            @else
                                Carta di credito
                            @endif
                        </div>
                    </td>
                    @if($firstOp?->product?->duration_minutes)
                        <td>
                            <div class="info-label">Durata</div>
                            <div class="info-value">{{ $firstOp->product->duration_minutes }} min</div>
                        </td>
                    @endif
                    @if($order->partner?->domain_name)
                        <td>
                            <div class="info-label">Canale di vendita</div>
                            <div class="info-value">{{ $order->partner->domain_name }}</div>
                        </td>
                    @endif
                </tr>
            @endif
        </table>
    </div>

    {{-- BIGLIETTI --}}
    <div class="section">
        <div class="section-title">Biglietti</div>
        <table class="tickets">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th class="center">Q.tà</th>
                    <th class="right">Prezzo</th>
                    <th class="right">Subtotale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderProducts as $op)
                    @if($op->items->isNotEmpty())
                        @foreach($op->items as $item)
                            <tr>
                                <td class="ticket-name">{{ $item->variant?->label ?? $op->product?->label ?? '—' }}</td>
                                <td class="center">{{ $item->quantity }}</td>
                                <td class="right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                                <td class="right">{{ number_format($item->subtotal, 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                    @else
                        @php($unit = $op->quantity > 0 ? $op->total / $op->quantity : $op->total)
                        <tr>
                            <td class="ticket-name">{{ $op->product?->label ?? '—' }}</td>
                            <td class="center">{{ $op->quantity }}</td>
                            <td class="right">{{ number_format($unit, 2, ',', '.') }} €</td>
                            <td class="right">{{ number_format($op->total, 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- TOTALI --}}
    <table class="totals-wrap">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;">
                <table class="totals">
                    <tr>
                        <td>Subtotale</td>
                        <td class="right">{{ number_format($order->amount, 2, ',', '.') }} €</td>
                    </tr>
                    <tr class="total-line">
                        <td>Totale ordine</td>
                        <td class="right">{{ number_format($order->amount, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- DETTAGLI CLIENTE --}}
    <div class="section">
        <div class="section-title">Dettagli cliente</div>
        <div class="customer">
            <div class="name">{{ $order->customer->full_name }}</div>
            @if($order->customer->email)<div class="row">{{ $order->customer->email }}</div>@endif
            @if($order->customer->phone)<div class="row">{{ trim(($order->customer->prefix_phone ?? '') . ' ' . $order->customer->phone) }}</div>@endif
            @if($order->customer->address)<div class="row">{{ $order->customer->full_address }}</div>@endif
            @if($order->customer->country)<div class="row">{{ $order->customer->country->name }}</div>@endif
            @if($order->customer->fiscal_code)<div class="row">C.F. {{ $order->customer->fiscal_code }}</div>@endif
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="receipt-footer">
        <div class="powered">
            <span class="label">Servizio offerto da</span>
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Miticko">
            @else
                <strong style="font-size: 14px; letter-spacing: 1px; color: #0D0D0D;">MITICKO</strong>
            @endif
        </div>
        <div class="legal">
            Documento generato in data {{ now()->translatedFormat('j F Y') }} — non costituisce documento fiscale
        </div>
    </div>
</body>
</html>
