@php
    $effettuatoIl = $order->paid_at ?? $order->created_at;
@endphp

<div class="order-preview">
    <div class="order-preview-section">
        <h6 class="order-preview-title">Dettagli ordine</h6>
        <div class="order-preview-grid">
            <div>
                <div class="order-preview-label">Numero ordine</div>
                <div class="order-preview-value">{{ $order->product_label }}</div>
            </div>
            <div>
                <div class="order-preview-label">Effettuato il</div>
                <div class="order-preview-value">
                    {{ $effettuatoIl?->translatedFormat('d F Y') }} @ {{ $effettuatoIl?->format('H:i') }}
                </div>
            </div>
            <div class="order-preview-status">
                @include('backoffice.components.label', [
                    'icon' => $order->order_status->icon(),
                    'status' => $order->order_status->status(),
                    'label' => $order->order_status->label(),
                ])
            </div>
        </div>
    </div>

    <div class="order-preview-section">
        <h6 class="order-preview-title">Dettagli vendita</h6>
        <table class="order-preview-table">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th class="text-center">n°</th>
                    <th class="text-end">Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderProducts as $op)
                    <tr class="order-preview-product">
                        <td colspan="3"><strong>{{ $op->product->label }}</strong></td>
                    </tr>
                    @foreach($op->items as $item)
                        <tr>
                            <td class="ps-3">{{ $item->variant?->label }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->subtotal, 2, ',', '.') }}€</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="order-preview-total">
                    <td colspan="2" class="text-end"><strong>Totale</strong></td>
                    <td class="text-end"><strong>{{ number_format($order->amount, 2, ',', '.') }}€</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="order-preview-section">
        <h6 class="order-preview-title">Dettagli cliente</h6>
        <div class="order-preview-customer">
            {{ $order->customer->full_name }}
            @if($order->customer->phone)
                <span class="order-preview-phone">{{ $order->customer->prefix_phone }} {{ $order->customer->phone }}</span>
            @endif
        </div>
    </div>

    <div class="order-preview-actions">
        <a href="{{ route('orders.show', $order) }}" class="btn btn-primary">
            Vai all'ordine <i class="fa-regular fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>
