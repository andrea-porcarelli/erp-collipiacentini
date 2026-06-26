<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public bool $refundIssued,
        public ?float $refundAmount = null,
    ) {}

    public function envelope(): Envelope
    {
        $partnerName = $this->order->partner?->partner_name;
        $subject = "Annullamento ordine #{$this->order->order_number}";

        if ($partnerName) {
            $subject = "{$partnerName} - {$subject}";
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $this->order->loadMissing([
            'partner.logo',
            'orderProducts.product',
        ]);

        return new Content(
            view: 'emails.order-cancellation',
            with: [
                'order'        => $this->order,
                'refundIssued' => $this->refundIssued,
                'refundAmount' => $this->refundAmount,
            ],
        );
    }
}
