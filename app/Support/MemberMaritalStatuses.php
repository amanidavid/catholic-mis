<?php

namespace App\Support;

final class MemberMaritalStatuses
{
    public const SINGLE = 'single';
    public const SACRAMENTAL_MARRIED_CATHOLIC = 'sacramental_married_catholic';
    public const SACRAMENTAL_MARRIED_NON_CATHOLIC = 'sacramental_married_non_catholic';
    public const NON_SACRAMENTAL_TRADITIONAL = 'non_sacramental_marriage_traditional';
    public const CIVIL_MARRIAGE = 'civil_marriage';

    public const LEGACY_MARRIED = 'married';
    public const LEGACY_WIDOWED = 'widowed';
    public const LEGACY_DIVORCED = 'divorced';

    public static function formOptions(?string $current = null): array
    {
        $options = self::labels();

        $current = is_string($current) ? trim($current) : '';
        if ($current !== '' && ! array_key_exists($current, $options) && array_key_exists($current, self::legacyLabels())) {
            $options[$current] = self::legacyLabels()[$current];
        }

        return $options;
    }

    public static function allowedValues(): array
    {
        return array_keys(self::labels() + self::legacyLabels());
    }

    public static function labels(): array
    {
        return [
            self::SINGLE => 'Single',
            self::SACRAMENTAL_MARRIED_CATHOLIC => 'Sacramental married (Catholic)',
            self::SACRAMENTAL_MARRIED_NON_CATHOLIC => 'Sacramental married (Non-Catholic)',
            self::NON_SACRAMENTAL_TRADITIONAL => 'Non-sacramental marriage (Traditional)',
            self::CIVIL_MARRIAGE => 'Civil marriage',
        ];
    }

    public static function legacyLabels(): array
    {
        return [
            self::LEGACY_MARRIED => 'Legacy: Married',
            self::LEGACY_WIDOWED => 'Legacy: Widowed',
            self::LEGACY_DIVORCED => 'Legacy: Divorced',
        ];
    }

    public static function label(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return self::labels()[$value]
            ?? self::legacyLabels()[$value]
            ?? ucwords(str_replace('_', ' ', $value));
    }

    public static function isMarriageUnion(?string $value): bool
    {
        return in_array($value, [
            self::SACRAMENTAL_MARRIED_CATHOLIC,
            self::SACRAMENTAL_MARRIED_NON_CATHOLIC,
            self::NON_SACRAMENTAL_TRADITIONAL,
            self::CIVIL_MARRIAGE,
            self::LEGACY_MARRIED,
        ], true);
    }
}
