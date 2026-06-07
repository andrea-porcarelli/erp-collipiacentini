@php
    $firstOp = $order->orderProducts->first();
    $eventTitle = $firstOp?->product?->label ?? '';
    $eventDate = $firstOp?->booking_date
        ? \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j F Y')
        : '';
    $eventTime = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : '';
    $eventDateTime = trim($eventDate . ($eventTime !== '' ? ' / ore ' . $eventTime : ''));

    $totalTickets = (int) $order->orderProducts->sum('quantity');
    $ticketsLabel = $totalTickets === 1 ? '1 persona' : $totalTickets . ' persone';
@endphp

<x-mail.layout
    :title="'Conferma ordine #' . $order->order_number"
    :partner-name="$order->partner?->partner_name"
    :brand="$order->partner?->brand"
    :preheader="'La tua prenotazione è confermata · ' . $order->order_number"
>
    <x-mail.card status="success" title="Prenotazione confermata">
        <p style="margin:0 0 12px 0;">Ciao {{ $order->customer->name }},</p>
        <p style="margin:0 0 20px 0;">
            la tua prenotazione è confermata: trovi qui sotto il riepilogo e in allegato il PDF da presentare all'ingresso.
        </p>

        <x-mail.event-heading :title="$eventTitle" :datetime="$eventDateTime" />

        <x-mail.data-grid>
            <tr>
                <x-mail.data-cell label="Biglietti" :value="$ticketsLabel" />
                <x-mail.data-cell label="Totale" :value="number_format($order->amount, 2, ',', '.') . ' €'" />
            </tr>
            <tr>
                <x-mail.data-cell label="Codice ordine" :value="$order->order_number" />
                <x-mail.data-cell label="Nome" :value="$order->customer->full_name" />
            </tr>
        </x-mail.data-grid>
    </x-mail.card>
</x-mail.layout>
