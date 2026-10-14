<?php

namespace App\Enums;

enum FarmerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active => 'badge-green',
            self::Inactive => 'badge-red',
        };
    }
}
