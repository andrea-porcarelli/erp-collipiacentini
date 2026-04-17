<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public static function statuses(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
    public function label(): string
    {
        return match($this) {
            self::PENDING => __('orders.status.pending'),
            self::PAID => __('orders.status.paid'),
            self::COMPLETED => __('orders.status.completed'),
            self::FAILED => __('orders.status.failed'),
            self::CANCELLED => __('orders.status.cancelled'),
            self::REFUNDED => __('orders.status.refunded'),
        };
    }

    public function status(): string
    {
        return match($this) {
            self::PENDING => 'Warning',
            self::PAID => 'Success',
            self::COMPLETED => 'Success',
            self::FAILED => 'Danger',
            self::CANCELLED => 'Danger',
            self::REFUNDED => 'Disabled',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'triangle-exclamation',
            self::PAID => 'check',
            self::COMPLETED => 'check',
            self::FAILED => 'xmark',
            self::CANCELLED => 'xmark',
            self::REFUNDED => 'money-bill-transfer',
        };
    }
}
