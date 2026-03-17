<?php

namespace App\Support;

class PhoneNormalizer
{
    public const TZ_REGEX = '/^(\+255|0)?[67]\d{8}$/';

    public static function normalize(?string $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $raw = preg_replace('/\s+/', '', $phone) ?: '';
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        if (str_starts_with($raw, '+') && str_starts_with($digits, '255') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+255'.substr($digits, 1);
        }

        if ((str_starts_with($digits, '6') || str_starts_with($digits, '7')) && strlen($digits) === 9) {
            return '+255'.$digits;
        }

        return $raw;
    }
}
