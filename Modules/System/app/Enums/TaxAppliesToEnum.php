<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxAppliesToEnum: string implements HasColor, HasIcon, HasLabel
{
    case Venta = 'VENTA';
    case Compra = 'COMPRA';
    case Ambos = 'AMBOS';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Venta->value => self::Venta->getLabel(),
            self::Compra->value => self::Compra->getLabel(),
            self::Ambos->value => self::Ambos->getLabel(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Venta->value,
            self::Compra->value,
            self::Ambos->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Venta => __('Sales'),
            self::Compra => __('Purchases'),
            self::Ambos => __('Both'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Venta => 'success',
            self::Compra => 'info',
            self::Ambos => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Venta => Heroicon::CurrencyDollar,
            self::Compra => Heroicon::ShoppingCart,
            self::Ambos => Heroicon::AdjustmentsHorizontal,
        };
    }
}
