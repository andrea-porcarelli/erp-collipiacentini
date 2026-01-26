<?php

namespace App\Enums;

enum ProductStatus: string
{
    case ACTIVE = '1';
    case INACTIVE = '0';

    public static function statuses(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => __('products.status.active'),
            self::INACTIVE => __('products.status.inactive'),
        };
    }

    public function class(): string
    {
        return match($this) {
            self::ACTIVE => 'active',
            self::INACTIVE => 'inactive',
        };
    }
}
