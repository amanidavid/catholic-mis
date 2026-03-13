<?php

namespace App\Traits;

class NormalizesNames
{
    public static function normalize(?string $value, bool $nullable = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = preg_replace('/\s+/u', ' ', trim($value));
        $v = $v === null ? '' : $v;

        if ($v === '') {
            return $nullable ? null : '';
        }

        $lower = mb_strtolower($v, 'UTF-8');

        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }
}
