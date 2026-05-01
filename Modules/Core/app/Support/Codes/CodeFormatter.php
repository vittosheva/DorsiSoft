<?php

declare(strict_types=1);

namespace Modules\Core\Support\Codes;

final class CodeFormatter
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtoupper(mb_trim($value));

        return $normalized === '' ? null : $normalized;
    }

    public static function present(?string $value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        if (preg_match('/^([A-Z]+)(\d+)$/', $normalized, $matches) === 1) {
            return "{$matches[1]} {$matches[2]}";
        }

        return $normalized;
    }
}
