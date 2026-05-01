<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountTypeEnum: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => '%',
            self::Fixed => '$',
        };
    }
}
