<?php

namespace App\Enums;

enum MilkType: string
{
    case Cow = 'cow';
    case Buffalo = 'buffalo';
    case A2 = 'a2';

    public function label(): string
    {
        return match($this) {
            self::Cow => 'Cow',
            self::Buffalo => 'Buffalo',
            self::A2 => 'A2 Premium',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Cow => '#10B981',
            self::Buffalo => '#3B82F6',
            self::A2 => '#F59E0B',
        };
    }
}
