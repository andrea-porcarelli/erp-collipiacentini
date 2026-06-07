@php
    use Carbon\Carbon;

    $createdAt = $order->created_at;

    $firstOp       = $order->orderProducts->first();
    $productLabel  = $firstOp?->product?->label;
    $bookingDate   = $firstOp?->booking_date ? Carbon::parse($firstOp->booking_date) : null;
    $bookingTime   = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : null;
    $isPastBooking = $bookingDate ? $bookingDate->lt(today()) : false;

    $itemsById = $order->orderProducts->flatMap->items->keyBy('id');

    $participants = $order->participants->sortBy('id')->values();
    $totalTickets = $participants->count();
    $checkedIn    = $participants->where('status', 'checked_in')->count();

    $salesRows = $order->orderProducts->flatMap->items
        ->groupBy(fn ($i) => $i->variant?->label ?? 'Biglietto')
        ->map(function ($items) {
            $qty      = (int) $items->sum('quantity');
            $unit     = (float) $items->first()?->unit_price;
            $subtotal = (float) $items->sum(fn ($i) => $i->unit_price * $i->quantity);

            return ['qty' => $qty, 'unit' => $unit, 'subtotal' => $subtotal];
        });

    $paymentLabel = $order->order_status->label();
    $cardLabel = $order->card_brand
        ? trim(ucfirst($order->card_brand) . ' ·· ' . ($order->card_last4 ?? ''))
        : ($order->stripe_payment_method ? 'Carta di credito' : '—');

    $statusOptions = [
        'booked'     => 'Prenotato',
        'checked_in' => 'Presentato',
        'no_show'    => 'Non presentato',
        'cancelled'  => 'Annullato',
    ];

    $phoneDigits = $order->customer
        ? preg_replace('/\D+/', '', (string) ($order->customer->prefix_phone ?? '') . (string) ($order->customer->phone ?? ''))
        : '';
@endphp

<div class="ticket-scanner-content" data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}">
    <div class="ts-section ts-header">
        <div class="ts-order-code">#MTK-{{ $order->order_number }}</div>
        @if($createdAt)
            <div class="ts-order-date">Creato il {{ $createdAt->translatedFormat('d F Y') }} alle {{ $createdAt->format('H:i') }}</div>
        @endif
    </div>

    <div class="ts-section ts-customer-section">
        <div class="ts-customer-line">
            <span class="ts-customer-name">{{ $order->customer?->full_name }}</span>
            @if($phoneDigits)
                <a href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener" class="ts-whatsapp" title="Apri WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
            @endif
        </div>
        @if($productLabel || $bookingDate || $bookingTime)
            <div class="ts-experience">
                <span class="ts-experience-label">{{ $productLabel }}</span>
                <div class="ts-experience-meta">
                    @if($bookingDate)
                        <span class="ts-experience-date {{ $isPastBooking ? 'ts-experience-date-past' : '' }}">
                            {{ $bookingDate->translatedFormat('d M Y') }}
                            @if($isPastBooking)
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            @endif
                        </span>
                    @endif
                    @if($bookingTime)
                        <span class="ts-experience-time">Ore {{ $bookingTime }}</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="ts-section ts-checkin">
        <div class="ts-section-title">Check-in visitatori</div>

        <div class="ts-checkin-counter">
            <div class="ts-checkin-counter-main">
                <span data-role="checkin-count">{{ $checkedIn }}</span> / {{ $totalTickets }} arrivati
            </div>
            <div class="ts-checkin-counter-sub">{{ $totalTickets }} {{ $totalTickets === 1 ? 'visitatore atteso' : 'visitatori attesi' }}</div>
        </div>

        <button type="button" class="ts-btn-all-arrived" data-role="all-arrived">
            <i class="fa-solid fa-check"></i> Arrivati tutti
        </button>

        <ul class="ts-tickets-list">
            @foreach($participants as $index => $participant)
                @php
                    $variantLabel = $itemsById->get($participant->order_product_item_id)?->variant?->label ?? '—';
                    $displayCode  = sprintf('MTK-%s-%02d', $order->order_number, $index + 1);
                    $isScanned    = isset($scannedParticipant) && $scannedParticipant === $participant->id;
                @endphp
                <li class="ts-ticket-row {{ $isScanned ? 'ts-ticket-row-scanned' : '' }}" data-participant-id="{{ $participant->id }}">
                    <div class="ts-ticket-info">
                        <div class="ts-ticket-title">Biglietto {{ $index + 1 }} · <span class="ts-ticket-variant">{{ $variantLabel }}</span></div>
                        <div class="ts-ticket-code">{{ $displayCode }}</div>
                    </div>
                    <div class="ts-ticket-status">
                        <select class="ts-status-select ts-status-{{ $participant->status }}" data-role="status-select" data-original="{{ $participant->status }}">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $participant->status === $value ? 'selected' : '' }}>{{ mb_strtoupper($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                </li>
            @endforeach
        </ul>

        <button type="button" class="ts-btn-save-inline" data-role="save-changes">
            Salva
        </button>
    </div>

    <div class="ts-section">
        <div class="ts-section-title">Dettagli vendita</div>
        <table class="ts-sales-table">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th class="text-center">n°</th>
                    <th class="text-end">Prezzo</th>
                    <th class="text-end">Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesRows as $label => $data)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-center">{{ $data['qty'] }}</td>
                        <td class="text-end">{{ number_format($data['unit'], 2, ',', '.') }}€</td>
                        <td class="text-end">{{ number_format($data['subtotal'], 2, ',', '.') }}€</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                    <td class="text-end ts-sales-total">{{ number_format($order->amount, 2, ',', '.') }}€</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="ts-section ts-payment">
        <div class="ts-payment-row">
            <div class="ts-field-label">Stato pagamento</div>
            <div class="ts-field-readonly">{{ $paymentLabel }}</div>
        </div>
        <div class="ts-payment-row">
            <div class="ts-field-label">Metodo pagamento</div>
            <div class="ts-field-readonly">{{ $cardLabel }}</div>
        </div>
    </div>

    @if($order->customer_note || $order->internal_note)
        <div class="ts-section ts-notes">
            @if($order->customer_note)
                <div class="ts-note">
                    <div class="ts-note-title"><i class="fa-regular fa-comment"></i> Note cliente</div>
                    <div class="ts-note-body">{{ $order->customer_note }}</div>
                </div>
            @endif
            @if($order->internal_note)
                <div class="ts-note">
                    <div class="ts-note-title"><i class="fa-solid fa-lock"></i> Note interne</div>
                    <div class="ts-note-body">{{ $order->internal_note }}</div>
                </div>
            @endif
        </div>
    @endif

    <div class="ts-section ts-go-to-order">
        <a href="{{ route('orders.show', $order) }}" class="ts-btn-go-order">Vai all'ordine</a>
    </div>
</div>
