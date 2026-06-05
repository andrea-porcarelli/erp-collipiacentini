<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $partnerName = $this->order->partner?->partner_name;
        $subject = "Conferma ordine #{$this->order->order_number}";

        if ($partnerName) {
            $subject = "{$partnerName} - {$subject}";
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: ['order' => $this->order],
        );
    }

    public function attachments(): array
    {
        $this->order->loadMissing([
            'customer.country',
            'partner',
            'orderProducts.product.category',
            'orderProducts.items.variant',
            'participants',
        ]);

        $pdf = Pdf::loadView('backoffice.orders._receipt', ['order' => $this->order]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "biglietto-MTK-{$this->order->order_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
