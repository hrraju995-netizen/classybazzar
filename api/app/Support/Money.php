<?php

namespace App\Support;

/**
 * Every money value in the database is an integer number of poisha
 * (1 BDT = 100 poisha). Floats are only ever produced at the API edge,
 * so no arithmetic in this application can drift by a rounding error.
 */
final class Money
{
    public static function toTaka(int $poisha): float
    {
        return round($poisha / 100, 2);
    }

    public static function toPoisha(float|int|string $taka): int
    {
        return (int) round(((float) $taka) * 100);
    }

    public static function format(int $poisha): string
    {
        return '৳'.number_format(self::toTaka($poisha), 2);
    }
}
