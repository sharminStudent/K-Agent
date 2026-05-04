<?php

namespace App\Support;

class BahrainPhone
{
    public static function localDigits(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value ?? '');

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '973')) {
            $digits = substr($digits, 3);
        }

        return $digits !== '' ? $digits : null;
    }

    public static function normalizeForStorage(?string $value): ?string
    {
        $digits = static::localDigits($value);

        if (blank($digits)) {
            return null;
        }

        return '+973 '.$digits;
    }
}
