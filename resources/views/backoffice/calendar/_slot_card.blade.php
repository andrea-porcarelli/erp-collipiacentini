@php
    $booked = (int) ($slot['booked'] ?? 0);
    $capacity = $slot['capacity'] ?? null;
    $percent = $capacity && $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : ($booked > 0 ? 100 : 0);
    $isFull = ! is_null($capacity) && $booked >= $capacity && $capacity > 0;
    $isEmpty = $booked === 0;
    $showLabel = $showLabel ?? false;
@endphp
<button type="button"
        class="calendar-slot-card js-slot-card @if($isFull) is-full @endif @if($isEmpty) is-empty @endif"
        data-product-id="{{ $product->id }}"
        data-product-label="{{ $product->label }}"
        data-date="{{ $date }}"
        data-time="{{ $slot['time'] }}">
    <div class="calendar-slot-top">
        <span class="calendar-slot-time">{{ $slot['time'] }}</span>
        <span class="calendar-slot-participants">
            {{ $booked }}
            <i class="fa-solid fa-user"></i>
        </span>
    </div>
    @if($showLabel)
        <div class="calendar-slot-product-label">{{ $product->label }}</div>
    @endif
    <div class="calendar-slot-bar">
        <div class="calendar-slot-bar-fill" style="width: {{ $percent }}%"></div>
    </div>
    <div class="calendar-slot-bottom">
        <span>{{ $slot['orders_count'] ?? 0 }} ordini</span>
        <span>{{ $booked }}/{{ is_null($capacity) ? '∞' : $capacity }}</span>
    </div>
</button>
