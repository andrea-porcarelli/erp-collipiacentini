@php
    $effettuatoIl = $order->paid_at ?? $order->created_at;
@endphp

<div class="order-preview">
    <div class="order-preview-section">
        <div class="order-preview-title mt-spacing-2xl mb-spacing-xl">Dettagli ordine</div>
        <div class="order-preview-grid">
            <div>
                <div class="order-preview-label mb-spacing-l">Numero ordine</div>
                <div class="order-preview-value">{{ $order->order_number }}</div>
            </div>
            <div>
                <div class="order-preview-label mb-spacing-l">Effettuato il</div>
                <div class="order-preview-value">
                    {{ $effettuatoIl?->translatedFormat('d F Y') }} @ {{ $effettuatoIl?->format('H:i') }}
                </div>
            </div>
            <div class="order-preview-status">
                <div class="order-preview-label mb-spacing-l">Stato</div>
                @include('backoffice.components.label', [
                    'status' => $order->order_status->status(),
                    'label' => $order->order_status->label(),
                ])
            </div>
        </div>
    </div>
    <hr style="background: var(--border-color)" />
    <div class="order-preview-section mb-spacing-2xl">
        <div class="order-preview-title mb-spacing-xl">Dettagli vendita</div>
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
                        <td colspan="3"><b>{{ $op->product->label }}</b></td>
                    </tr>
                    @foreach($op->items as $item)
                        <tr>
                            <td>{{ $item->variant?->label }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->subtotal, 2, ',', '.') }}€</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="order-preview-total">
                    <td colspan="2" class="text-end"> </td>
                    <td class="text-end">{{ number_format($order->amount, 2, ',', '.') }}€</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <hr style="background: var(--border-color); margin: 0" />
    <div class="order-preview-section mt-spacing-2xl mb-spacing-2xl">
        <div class="order-preview-title mb-spacing-xl">Dettagli cliente</div>
        <div class="order-preview-customer">
            <span>{{ $order->customer->full_name }}</span>
            @if($order->customer->phone)
                <span class="order-preview-phone">{{ trim($order->customer->prefix_phone . ' ' . $order->customer->phone) }}</span>
            @endif
        </div>
    </div>
    <hr style="background: var(--border-color); margin: 0" />
    <div class="order-preview-actions">
        <x-button
            :href="route('orders.show', $order)"
            label="Vai all'ordine"
            status="Primary"
            emphasis="Medium"
            size="Small"
            trailing="fa-chevron-right"
        />
    </div>
</div>
