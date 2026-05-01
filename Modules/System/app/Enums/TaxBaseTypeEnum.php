<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxBaseTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Precio = 'PRECIO';
    case Utilidad = 'UTILIDAD';
    case Cantidad = 'CANTIDAD';
    case Otro = 'OTRO';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Precio->value => self::Precio->getLabel(),
            self::Utilidad->value => self::Utilidad->getLabel(),
            self::Cantidad->value => self::Cantidad->getLabel(),
            self::Otro->value => self::Otro->getLabel(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Precio->value,
            self::Utilidad->value,
            self::Cantidad->value,
            self::Otro->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Precio => __('Price'),
            self::Utilidad => __('Profit'),
            self::Cantidad => __('Quantity'),
            self::Otro => __('Other'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Precio => 'primary',
            self::Utilidad => 'success',
            self::Cantidad => 'info',
            self::Otro => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Precio => Heroicon::CurrencyDollar,
            self::Utilidad => Heroicon::ChartBar,
            self::Cantidad => Heroicon::Calculator,
            self::Otro => Heroicon::QuestionMarkCircle,
        };
    }
}
