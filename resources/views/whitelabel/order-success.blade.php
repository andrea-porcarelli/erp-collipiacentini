@extends('whitelabel.layout', compact('company'))

@section('content')
    <div class="container mt-5" style="min-height: 600px">
        <div class="row w-100">
            <div class="col-12 col-sm-6 offset-sm-3">
                <div class="row w-100">
                    <div class="col-12 text-center hero">
                        <div class="success-icon">
                            <i class="fa-regular fa-circle-check"></i>
                        </div>
                        <h1>Ordine confermato!</h1>
                        <p class="success-subtitle">Grazie per il tuo acquisto</p>
                    </div>
                </div>

                <x-card class="order-success-card" h1="true" leading="fa-ticket">
                    <div class="order-details">
                        <div class="order-header">
                            <h3>Dettagli ordine</h3>
                            <p class="order-number">Ordine #{{ $order->order_number }}</p>
                        </div>

                        <div class="order-info">
                            <div class="info-row">
                                <span class="info-label">Data ordine</span>
                                <span class="info-value">{{ $order->created_at->locale('it')->isoFormat('D MMMM YYYY, HH:mm') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Stato</span>
                                <span class="info-value status-badge status-{{ strtolower($order->order_status->value) }}">
                                    {{ $order->order_status->label() }}
                                </span>
                            </div>
                        </div>

                        @foreach($order->orderProducts as $orderProduct)
                            <div class="product-summary">
                                <h4>{{ $orderProduct->product->meta_title }}</h4>

                                <div class="booking-details">
                                    @if($orderProduct->booking_date)
                                        <div class="detail-item">
                                            <i class="fa-regular fa-calendar"></i>
                                            <span>{{ \Carbon\Carbon::parse($orderProduct->booking_date)->locale('it')->isoFormat('ddd D MMMM YYYY') }}</span>
                                        </div>
                                    @endif
                                    @if($orderProduct->booking_time)
                                        <div class="detail-item">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>{{ \Carbon\Carbon::parse($orderProduct->booking_time)->format('H:i') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="tickets-summary">
                                    @if($orderProduct->quantity_full > 0)
                                        <div class="ticket-row">
                                            <span>{{ $orderProduct->quantity_full }} Intero</span>
                                            <span>{{ Utils::price($orderProduct->price_full * $orderProduct->quantity_full) }}</span>
                                        </div>
                                    @endif
                                    @if($orderProduct->quantity_reduced > 0)
                                        <div class="ticket-row">
                                            <span>{{ $orderProduct->quantity_reduced }} Ridotto</span>
                                            <span>{{ Utils::price($orderProduct->price_reduced * $orderProduct->quantity_reduced) }}</span>
                                        </div>
                                    @endif
                                    @if($orderProduct->quantity_free > 0)
                                        <div class="ticket-row">
                                            <span>{{ $orderProduct->quantity_free }} Gratuito</span>
                                            <span>{{ Utils::price($orderProduct->price_free * $orderProduct->quantity_free) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="order-total">
                            <span class="total-label">Totale pagato</span>
                            <span class="total-amount">{{ Utils::price($order->amount) }}</span>
                        </div>

                        <div class="customer-info">
                            <h4>Dati acquirente</h4>
                            <p><strong>{{ $order->customer->full_name }}</strong></p>
                            <p>{{ $order->customer->email }}</p>
                            @if($order->customer->phone)
                                <p>{{ $order->customer->phone }}</p>
                            @endif
                        </div>

                        <div class="confirmation-message">
                            <i class="fa-regular fa-envelope"></i>
                            <p>Ti abbiamo inviato una email di conferma all'indirizzo <strong>{{ $order->customer->email }}</strong> con tutti i dettagli del tuo ordine.</p>
                        </div>
                    </div>
                </x-card>

                <div class="order-actions">
                    <a href="{{ url('/shop') }}" class="bt-miticko bt-m-default w-100" data-mode="small">
                        Torna ai prodotti <i class="fa-regular fa-arrow-right icon"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
    .success-icon {
        font-size: 80px;
        color: var(--success, #28a745);
        margin-bottom: 16px;
    }

    .success-subtitle {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-secondary, #666);
        margin-bottom: 24px;
    }

    .order-success-card {
        margin-top: 24px;
    }

    .order-details {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .order-header {
        border-bottom: 1px solid var(--neutral-grey-10, #E6E6E6);
        padding-bottom: 16px;
    }

    .order-header h3 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-medium, 20px);
        font-weight: var(--typography-title-weight-medium, 600);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 4px;
    }

    .order-number {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
        margin: 0;
    }

    .order-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-label {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
    }

    .info-value {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-main, #0D0D0D);
        font-weight: 500;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-paid {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success, #28a745);
    }

    .status-pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .status-failed {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--error, #DC3545);
    }

    .product-summary {
        background-color: var(--neutral-grey-2, #F5F5F5);
        border-radius: var(--border-radius-m, 8px);
        padding: 16px;
    }

    .product-summary h4 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 16px);
        font-weight: var(--typography-title-weight-small, 600);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 12px;
    }

    .booking-details {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 12px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
    }

    .detail-item i {
        color: var(--secondary-brand, #2A3493);
    }

    .tickets-summary {
        border-top: 1px solid var(--neutral-grey-10, #E6E6E6);
        padding-top: 12px;
    }

    .ticket-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-main, #0D0D0D);
    }

    .order-total {
        border-top: 1px solid var(--neutral-grey-10, #E6E6E6);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
    }

    .total-label {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-medium, 20px);
        font-weight: var(--typography-title-weight-medium, 600);
        color: var(--text-main, #0D0D0D);
    }

    .total-amount {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-large, 28px);
        font-weight: var(--typography-title-weight-large, 700);
        color: var(--secondary-brand, #2A3493);
    }

    .customer-info {
        border-top: 1px solid var(--neutral-grey-10, #E6E6E6);
        padding-top: 16px;
    }

    .customer-info h4 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 14px);
        font-weight: var(--typography-title-weight-small, 700);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 8px;
    }

    .customer-info p {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
        margin: 4px 0;
    }

    .confirmation-message {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background-color: rgba(42, 52, 147, 0.05);
        border-radius: var(--border-radius-m, 8px);
        padding: 16px;
    }

    .confirmation-message i {
        font-size: 24px;
        color: var(--secondary-brand, #2A3493);
        flex-shrink: 0;
    }

    .confirmation-message p {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-main, #0D0D0D);
        margin: 0;
    }

    .order-actions {
        margin-top: 24px;
        margin-bottom: 48px;
    }
</style>
@endpush
