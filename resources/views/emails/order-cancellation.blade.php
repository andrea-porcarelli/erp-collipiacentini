@php
    $firstOp = $order->orderProducts->first();
    $product = $firstOp?->product;
    $eventTitle = $product?->label ?? '';
    $eventDate = $firstOp?->booking_date
        ? \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j F Y')
        : '';
    $eventTime = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : '';
    $eventDateTime = trim($eventDate . ($eventTime !== '' ? ' / ore ' . $eventTime : ''));

    $partnerLogo = $order->partner?->logo;
    $partnerLogoPath = $partnerLogo
        ? \Illuminate\Support\Facades\Storage::disk('public')->path($partnerLogo->file_path)
        : null;
    $partnerLogoUrl = $partnerLogo
        ? asset('storage/' . $partnerLogo->file_path)
        : null;

    $supportEmail = $product?->support_email ?: config('mail.support_address');
@endphp

<x-mail.layout
    :title="'Annullamento ordine #' . $order->order_number"
    :partner-name="$order->partner?->partner_name"
    :brand="$order->partner?->brand"
    :preheader="'Il tuo ordine è stato annullato · ' . $order->order_number"
    :partner-logo-path="$partnerLogoPath"
    :partner-logo-url="$partnerLogoUrl"
    :support-email="$supportEmail"
>
    <x-mail.card status="danger" title="Prenotazione annullata">
        <p style="margin:0 0 12px 0;">Ciao {{ $order->customer->name }},</p>
        <p style="margin:0 0 20px 0;">
            ti informiamo che la tua prenotazione
            @if($eventTitle)
                per <strong>{{ $eventTitle }}</strong>
            @endif
            @if($eventDateTime)
                del {{ $eventDateTime }}
            @endif
            è stata annullata.
        </p>

        <x-mail.event-heading :title="$eventTitle" :datetime="$eventDateTime" />

        <x-mail.data-grid>
            <tr>
                <x-mail.data-cell label="Codice ordine" :value="$order->order_number" />
                <x-mail.data-cell label="Nome" :value="$order->customer->full_name" />
            </tr>
        </x-mail.data-grid>

        @if($refundIssued)
            <p style="margin:24px 0 0 0;">
                Hai ricevuto un rimborso integrale di
                <strong>{{ number_format($refundAmount ?? 0, 2, ',', '.') }} €</strong>.
                L'importo sarà accreditato sul tuo metodo di pagamento entro 5–10 giorni lavorativi.
            </p>
        @else
            <p style="margin:24px 0 0 0;">
                Nessun rimborso è previsto per questa cancellazione. Per qualsiasi chiarimento puoi rispondere a questa email.
            </p>
        @endif
    </x-mail.card>
</x-mail.layout>
