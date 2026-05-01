<?php

declare(strict_types=1);

namespace Modules\Finance\Support;

final class CollectionAllocationMath
{
    public const int SCALE = 4;

    public const string ROUNDING_TOLERANCE = '0.0050';

    public static function normalize(mixed $amount): string
    {
        $normalizedAmount = str_replace(',', '.', mb_trim((string) $amount));

        return number_format((float) $normalizedAmount, self::SCALE, '.', '');
    }

    public static function pending(mixed $total, mixed $paid): string
    {
        $pendingAmount = bcsub(self::normalize($total), self::normalize($paid), self::SCALE);

        if (self::isEffectivelyZero($pendingAmount)) {
            return '0.0000';
        }

        return bccomp($pendingAmount, '0.0000', self::SCALE) < 0
            ? '0.0000'
            : $pendingAmount;
    }

    public static function isEffectivelyZero(mixed $amount): bool
    {
        return bccomp(self::absolute($amount), self::ROUNDING_TOLERANCE, self::SCALE) <= 0;
    }

    public static function exceedsWithTolerance(mixed $amount, mixed $limit): bool
    {
        $allowedLimit = bcadd(self::normalize($limit), self::ROUNDING_TOLERANCE, self::SCALE);

        return bccomp(self::normalize($amount), $allowedLimit, self::SCALE) > 0;
    }

    public static function isPaid(mixed $paid, mixed $total): bool
    {
        $requiredPaid = bcsub(self::normalize($total), self::ROUNDING_TOLERANCE, self::SCALE);

        return bccomp(self::normalize($paid), $requiredPaid, self::SCALE) >= 0;
    }

    private static function absolute(mixed $amount): string
    {
        $normalizedAmount = self::normalize($amount);

        if (bccomp($normalizedAmount, '0.0000', self::SCALE) >= 0) {
            return $normalizedAmount;
        }

        return bcmul($normalizedAmount, '-1', self::SCALE);
    }
}
