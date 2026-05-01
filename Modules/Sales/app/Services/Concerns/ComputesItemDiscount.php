<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use Modules\Sales\Enums\DiscountTypeEnum;

/**
 * Shared item-level discount calculation for document totals calculators.
 */
trait ComputesItemDiscount
{
    private function computeItemDiscount(mixed $item, string $gross): string
    {
        if ($item->discount_value === null || $item->discount_type === null) {
            return '0.0000';
        }

        if ($item->discount_type === DiscountTypeEnum::Percentage) {
            return bcmul($gross, bcdiv((string) $item->discount_value, '100', 8), 4);
        }

        return min((string) $item->discount_value, $gross);
    }
}
