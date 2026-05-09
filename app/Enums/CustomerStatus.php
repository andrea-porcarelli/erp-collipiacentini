<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case BOOKED = 'booked';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
    case CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match($this) {
            self::BOOKED => __('orders.customer_status.booked'),
            self::CONFIRMED => __('orders.customer_status.confirmed'),
            self::COMPLETED => __('orders.customer_status.completed'),
            self::NO_SHOW => __('orders.customer_status.no_show'),
            self::CANCELLED => __('orders.customer_status.cancelled'),
        };
    }

    public function status(): string
    {
        return match($this) {
            self::BOOKED => 'Warning',
            self::CONFIRMED => 'Success',
            self::COMPLETED => 'Success',
            self::NO_SHOW => 'Danger',
            self::CANCELLED => 'Danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::BOOKED => 'calendar-check',
            self::CONFIRMED => 'check',
            self::COMPLETED => 'flag-checkered',
            self::NO_SHOW => 'user-slash',
            self::CANCELLED => 'xmark',
        };
    }
}
