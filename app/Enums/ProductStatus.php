<?php

namespace App\Enums;

enum ProductStatus: int
{
    case PENDING     = 0;
    case ACTIVE      = 1;
    case UNAVAILABLE = 2;

    public static function statuses(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING     => __('products.status.pending'),
            self::ACTIVE      => __('products.status.active'),
            self::UNAVAILABLE => __('products.status.unavailable'),
        };
    }

    public function status(): string
    {
        return match($this) {
            self::PENDING     => 'Disabled',
            self::ACTIVE      => 'Success',
            self::UNAVAILABLE => 'Warning',
        };
    }
}
