<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
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
            self::COMPLETED => __('orders.status.completed'),
            self::CANCELLED => __('orders.status.cancelled'),
            self::REFUNDED => __('orders.status.refunded'),
        };
    }

    public function status(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'disabled',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'triangle-exclamation',
            self::COMPLETED => 'check',
            self::CANCELLED => 'xmark',
            self::REFUNDED => 'money-bill-transfer',
        };
    }
}
