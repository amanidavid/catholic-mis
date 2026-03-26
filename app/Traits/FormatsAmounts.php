<?php

namespace App\Traits;

trait FormatsAmounts
{
    public static function normalizeAmount(mixed $value, int $scale = 4): string
    {
        if ($value === null) {
            return number_format(0, $scale, '.', '');
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return number_format(0, $scale, '.', '');
            }

            // Remove thousands separators/spaces.
            $value = str_replace([',', ' '], '', $value);

            if (!is_numeric($value)) {
                return number_format(0, $scale, '.', '');
            }

            return number_format((float) $value, $scale, '.', '');
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, $scale, '.', '');
        }

        return number_format(0, $scale, '.', '');
    }

    public static function formatAmount(mixed $value, int $scale = 2): string
    {
        $normalized = self::normalizeAmount($value, $scale);
        return number_format((float) $normalized, $scale, '.', ',');
    }

    public static function signedOpeningBalance(mixed $openingBalance, ?string $type, int $scale = 4): string
    {
        $normalized = self::normalizeAmount($openingBalance, $scale);
        $amount = (float) $normalized;

        $t = is_string($type) ? strtolower(trim($type)) : 'debit';
        if ($t === 'credit') {
            $amount *= -1;
        }

        return number_format($amount, $scale, '.', '');
    }
}
