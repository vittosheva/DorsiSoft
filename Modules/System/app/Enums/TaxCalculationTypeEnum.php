<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxCalculationTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Mixed = 'mixed';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Percentage->value => self::Percentage->getLabel(),
            self::Fixed->value => self::Fixed->getLabel(),
            self::Mixed->value => self::Mixed->getLabel(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Percentage->value,
            self::Fixed->value,
            self::Mixed->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => __('Percentage'),
            self::Fixed => __('Fixed value'),
            self::Mixed => __('Mixed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Percentage => 'info',
            self::Fixed => 'warning',
            self::Mixed => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Percentage => Heroicon::PercentBadge,
            self::Fixed => Heroicon::Hashtag,
            self::Mixed => Heroicon::Calculator,
        };
    }
}
