<?php

namespace App\Enums;

enum AuthorSubscriptionDelivery: string
{
    case Instant = 'instant';
    case Daily = 'daily';
    case Off = 'off';

    public function label(): string
    {
        return match ($this) {
            self::Instant => 'Email instantly',
            self::Daily => 'Daily digest',
            self::Off => 'In-app only',
        };
    }
}
