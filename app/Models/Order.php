<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends LogsModel
{
    public const INVOICE_STATUS_TO_EMIT = 'to_emit';

    public const INVOICE_STATUS_NOT_REQUIRED = 'not_required';

    public const INVOICE_STATUS_SENT = 'sent';

    public const INVOICE_STATUS_REFUNDED = 'refunded';

    public $fillable = [
        'customer_id',
        'partner_id',
        'order_number',
        'amount',
        'order_status',
        'customer_status',
        'customer_note',
        'internal_note',
        'stripe_payment_intent_id',
        'stripe_payment_method',
        'card_brand',
        'card_last4',
        'paid_at',
        'payment_error',
        'stripe_payment_link_id',
        'stripe_payment_link_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer',
        'gclid',
        'fbclid',
        'name',
        'surname',
        'email',
        'prefix_phone',
        'phone',
        'address',
        'city',
        'zip_code',
        'country_id',
        'fiscal_code',
        'birth_date',
        'privacy_accepted',
        'newsletter',
    ];

    protected $casts = [
        'order_status' => OrderStatus::class,
        'customer_status' => CustomerStatus::class,
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'birth_date' => 'date',
        'privacy_accepted' => 'boolean',
        'newsletter' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OrderParticipant::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(OrderConsent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(sprintf('%s %s', $this->name ?? '', $this->surname ?? ''));
    }

    public function getFullAddressAttribute(): string
    {
        return trim(sprintf('%s, %s %s', $this->address ?? '', $this->zip_code ?? '', $this->city ?? ''), ' ,');
    }

    public function getProductTimeAttribute(): string
    {
        return isset($this->orderProducts()->first()->booking_time) ? substr($this->orderProducts()->first()->booking_time, 0, 5) : '';
    }

    public function getProductDataAttribute(): string
    {
        return $this->orderProducts()->first()->booking_date ?? '';
    }

    public function getProductLabelAttribute(): string
    {
        return $this->orderProducts()->first()->product->label ?? '';
    }

    /**
     * Stato fattura derivato on-the-fly. Nessuna persistenza.
     */
    public function invoiceStatus(): string
    {
        if ($this->order_status === OrderStatus::REFUNDED) {
            return self::INVOICE_STATUS_REFUNDED;
        }

        $this->loadMissing('orderProducts.product', 'invoices');

        $hasInvoiceRequiredProduct = $this->orderProducts
            ->contains(fn ($op) => (bool) ($op->product->invoice_required ?? false));

        if (! $hasInvoiceRequiredProduct) {
            return self::INVOICE_STATUS_NOT_REQUIRED;
        }

        // Fattura considerata "emessa" se esiste almeno un invoice collegato in uno degli stati
        // non ri-emittibili (draft/queued compresi: la pratica è già stata avviata, non va rifatta).
        // Solo error/rejected lasciano il bottone "Imposta fattura" attivo.
        $emittedInvoice = $this->invoices->contains(
            fn (Invoice $i) => in_array($i->status, [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_QUEUED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_ACCEPTED,
            ], true)
        );

        return $emittedInvoice ? self::INVOICE_STATUS_SENT : self::INVOICE_STATUS_TO_EMIT;
    }

    public function invoiceStatusLabel(): string
    {
        return match ($this->invoiceStatus()) {
            self::INVOICE_STATUS_TO_EMIT => 'Da emettere',
            self::INVOICE_STATUS_NOT_REQUIRED => 'Non necessaria',
            self::INVOICE_STATUS_SENT => 'Inviata',
            self::INVOICE_STATUS_REFUNDED => 'Rimborsato',
            default => '—',
        };
    }
}
