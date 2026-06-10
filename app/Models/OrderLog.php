<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderLog extends Model
{
    protected $fillable = [
        'order_id',
        'cart_id',
        'session_id',
        'causer_type',
        'causer_id',
        'causer_name',
        'event_type',
        'description',
        'properties',
        'context',
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'array',
        'context' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'order_created'           => 'Ordine creato',
            'order_paid'              => 'Pagamento completato',
            'order_failed'            => 'Pagamento fallito',
            'cart_started'            => 'Carrello avviato',
            'cart_updated'            => 'Carrello modificato',
            'cart_customer_assigned'  => 'Cliente associato',
            'cart_consents_accepted'  => 'Consensi accettati',
            'cart_removed'            => 'Carrello svuotato',
            'booking_changed'         => 'Data/orario modificati',
            'customer_status_changed' => 'Stato cliente aggiornato',
            'notes_updated'           => 'Note aggiornate',
            'customer_updated'        => 'Dati cliente aggiornati',
            'email_sent'              => 'Email inviata',
            'receipt_downloaded'      => 'Ricevuta scaricata',
            'checkin_changed'         => 'Check-in aggiornato',
            'refunded'                => 'Ordine rimborsato',
            default                   => 'Attività',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event_type) {
            'order_created'           => 'fa-cart-shopping',
            'order_paid'              => 'fa-credit-card',
            'order_failed'            => 'fa-circle-xmark',
            'cart_started'            => 'fa-basket-shopping',
            'cart_updated'            => 'fa-pen-to-square',
            'cart_customer_assigned'  => 'fa-user-plus',
            'cart_consents_accepted'  => 'fa-file-signature',
            'cart_removed'            => 'fa-trash',
            'booking_changed'         => 'fa-calendar-day',
            'customer_status_changed' => 'fa-user-check',
            'notes_updated'           => 'fa-note-sticky',
            'customer_updated'        => 'fa-user-pen',
            'email_sent'              => 'fa-envelope',
            'receipt_downloaded'      => 'fa-file-pdf',
            'checkin_changed'         => 'fa-circle-check',
            'refunded'                => 'fa-rotate-left',
            default                   => 'fa-circle-info',
        };
    }

    public function getEventGroupAttribute(): string
    {
        return match ($this->event_type) {
            'cart_started', 'cart_updated', 'cart_customer_assigned',
            'cart_consents_accepted', 'cart_removed' => 'cart',

            'order_created', 'order_paid', 'order_failed', 'refunded' => 'order',

            'booking_changed', 'customer_status_changed', 'notes_updated',
            'customer_updated' => 'edits',

            'email_sent', 'receipt_downloaded' => 'comms',

            'checkin_changed' => 'checkin',

            default => 'other',
        };
    }
}
