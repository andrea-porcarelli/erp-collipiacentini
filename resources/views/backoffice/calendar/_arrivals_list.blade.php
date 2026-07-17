@if($orders->isEmpty())
    <div class="calendar-arrivals-placeholder">
        <i class="fa fa-triangle-exclamation"></i>
        <p class="mb-0">Nessun risultato trovato</p>
    </div>
@else
    <ul class="calendar-arrivals-orders">
        @foreach($orders as $entry)
            @php
                $order = $entry['order'];
                $checkin = $entry['checkin'];
                $status = $order->order_status;
                $customer = $order->customer;
                $expected = (int) ($checkin['expected'] ?? 0);
                $checkedIn = (int) ($checkin['checked_in'] ?? 0);
                $checkinLabel = null;
                $checkinClass = null;
                if ($expected > 0 && $checkedIn >= $expected) {
                    $checkinLabel = 'CHECK-IN COMPLETO';
                    $checkinClass = 'success';
                } elseif ($checkedIn > 0) {
                    $checkinLabel = 'GIÀ ARRIVATI';
                    $checkinClass = 'info';
                }
            @endphp
            <li class="calendar-arrival-item js-arrival-item" data-order-id="{{ $order->id }}">
                <div class="calendar-arrival-main">
                    <div class="calendar-arrival-name">{{ trim(($customer->name ?? '').' '.($customer->surname ?? '')) ?: 'Cliente' }}</div>
                    <div class="calendar-arrival-number">MTK-{{ $order->order_number }}</div>
                    <div class="calendar-arrival-badges">
                        <span class="calendar-badge status-{{ $status?->status() ? strtolower($status->status()) : 'default' }}">
                            {{ strtoupper($status?->label() ?? '—') }}
                        </span>
                        @if($checkinLabel)
                            <span class="calendar-badge checkin-{{ $checkinClass }}">{{ $checkinLabel }}</span>
                        @endif
                    </div>
                </div>
                <div class="calendar-arrival-side">
                    <div class="calendar-arrival-count">
                        {{ $expected }}
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
