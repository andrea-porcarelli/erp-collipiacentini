<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma ordine #{{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0D0D0D; line-height: 1.5; max-width: 640px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        h2 { font-size: 16px; margin: 24px 0 8px; }
        .meta { color: #666; font-size: 13px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 8px 4px; text-align: left; border-bottom: 1px solid #E6E6E6; font-size: 14px; }
        th { color: #666; font-weight: 600; }
        .total td { border-top: 2px solid #0D0D0D; border-bottom: none; font-weight: 700; padding-top: 12px; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Conferma ordine #{{ $order->order_number }}</h1>
    <div class="meta">
        Effettuato il
        {{ ($order->paid_at ?? $order->created_at)->translatedFormat('j F Y') }}
        alle {{ ($order->paid_at ?? $order->created_at)->format('H:i') }}
    </div>

    <p>Ciao {{ $order->customer->name }},</p>
    <p>grazie per il tuo acquisto. Di seguito i dettagli del tuo ordine.</p>

    <h2>Dettagli ordine</h2>
    <p>
        <strong>Numero ordine:</strong> #{{ $order->order_number }}<br>
        @php($firstOp = $order->orderProducts->first())
        @if($firstOp)
            <strong>Data visita:</strong> {{ \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j F Y') }} ore {{ substr($firstOp->booking_time, 0, 5) }}<br>
        @endif
        <strong>Stato pagamento:</strong> {{ $order->order_status->label() }}
    </p>

    <h2>Biglietti</h2>
    <table>
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
                    <tr>
                        <td colspan="4"><strong>{{ $op->product->label }}</strong></td>
                    </tr>
                    @foreach($op->items as $item)
                        <tr>
                            <td>{{ $item->variant?->label ?? $op->product?->label }}</td>
                            <td class="center">{{ $item->quantity }}</td>
                            <td class="right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                            <td class="right">{{ number_format($item->subtotal, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                @else
                    @php($unit = $op->quantity > 0 ? $op->total / $op->quantity : $op->total)
                    <tr>
                        <td><strong>{{ $op->product?->label ?? '—' }}</strong></td>
                        <td class="center">{{ $op->quantity }}</td>
                        <td class="right">{{ number_format($unit, 2, ',', '.') }} €</td>
                        <td class="right">{{ number_format($op->total, 2, ',', '.') }} €</td>
                    </tr>
                @endif
            @endforeach
            <tr class="total">
                <td colspan="3" class="right">Totale</td>
                <td class="right">{{ number_format($order->amount, 2, ',', '.') }} €</td>
            </tr>
        </tbody>
    </table>

    <h2>Dettagli cliente</h2>
    <p>
        {{ $order->customer->full_name }}<br>
        @if($order->customer->email){{ $order->customer->email }}<br>@endif
        @if($order->customer->phone){{ trim($order->customer->prefix_phone . ' ' . $order->customer->phone) }}<br>@endif
        @if($order->customer->address){{ $order->customer->full_address }}@endif
    </p>

    <p style="margin-top: 32px; color: #666; font-size: 12px;">
        In caso di necessità contattaci rispondendo a questa email.
    </p>
</body>
</html>
