@php
    use Carbon\Carbon;

    $createdAt = $order->created_at;
    $firstOp = $order->orderProducts->first();
    $productLabel = $firstOp?->product?->label;
    $bookingDate = $firstOp?->booking_date ? Carbon::parse($firstOp->booking_date) : null;
    $bookingTime = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : null;

    $itemsById = $order->orderProducts->flatMap->items->keyBy('id');
    $participants = $order->participants->sortBy('id')->values();
    $totalTickets = $participants->count();
    $checkedIn = $participants->where('status', 'checked_in')->count();

    $statusOptions = [
        'booked'     => 'Prenotato',
        'checked_in' => 'Arrivato',
        'no_show'    => 'No show',
        'cancelled'  => 'Annullato',
    ];

    $phoneDigits = $order->customer
        ? preg_replace('/\D+/', '', (string) ($order->customer->prefix_phone ?? '') . (string) ($order->customer->phone ?? ''))
        : '';
@endphp

<div class="calendar-checkin-content" data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}">
    <div class="cc-section cc-header">
        <div class="cc-order-code">#MTK-{{ $order->order_number }}</div>
        @if($createdAt)
            <div class="cc-order-date">Creato il {{ $createdAt->translatedFormat('d F Y') }} alle {{ $createdAt->format('H:i') }}</div>
        @endif
    </div>

    <div class="cc-section cc-customer">
        <div class="cc-section-title">Dati cliente</div>
        <div class="cc-customer-line">
            <i class="fa-regular fa-user"></i>
            <span>{{ trim(($order->customer->name ?? '').' '.($order->customer->surname ?? '')) ?: 'Cliente' }}</span>
        </div>
        @if($order->customer?->email)
            <div class="cc-customer-line">
                <i class="fa-regular fa-envelope"></i>
                <span>{{ $order->customer->email }}</span>
            </div>
        @endif
        @if($order->customer?->phone)
            <div class="cc-customer-line">
                <i class="fa-solid fa-phone"></i>
                <span>{{ trim(($order->customer->prefix_phone ?? '').' '.$order->customer->phone) }}</span>
            </div>
        @endif
        <a class="cc-open-order" href="{{ route('orders.show', $order) }}" target="_blank" rel="noopener">
            Apri ordine <i class="fa fa-arrow-up-right-from-square"></i>
        </a>
    </div>

    <div class="cc-section cc-checkin">
        <div class="cc-checkin-counter">
            <div class="cc-counter-main">
                <span data-role="checkin-count">{{ $checkedIn }}</span> / {{ $totalTickets }} arrivati
            </div>
            <div class="cc-counter-sub">
                {{ $totalTickets }} {{ $totalTickets === 1 ? 'visitatore atteso' : 'visitatori attesi' }}
            </div>
        </div>
        @if($totalTickets > 0)
            <button type="button" class="cc-btn-all-arrived js-cc-all-arrived">
                <i class="fa-solid fa-check"></i> Segna tutti come arrivati
            </button>
        @endif

        <ul class="cc-tickets">
            @foreach($participants as $index => $participant)
                @php
                    $variantLabel = $itemsById->get($participant->order_product_item_id)?->variant?->label ?? '—';
                    $displayCode = sprintf('MTK-%s-%02d', $order->order_number, $index + 1);
                @endphp
                <li class="cc-ticket cc-status-{{ $participant->status }}" data-participant-id="{{ $participant->id }}">
                    <div class="cc-ticket-info">
                        <div class="cc-ticket-title">Prenotazione {{ $index + 1 }} · <span class="cc-ticket-variant">{{ $variantLabel }}</span></div>
                        <div class="cc-ticket-code">{{ $displayCode }}</div>
                    </div>
                    <div class="cc-ticket-status">
                        <select class="cc-status-select js-cc-status" data-original="{{ $participant->status }}">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @if($participant->status === $value) selected @endif>
                                    {{ mb_strtoupper($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
