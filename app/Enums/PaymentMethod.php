<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case Cash = 'cash';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Cash => 'Dinheiro',
            self::Card => 'Cartão',
            self::Other => 'Outro',
        };
    }
}
