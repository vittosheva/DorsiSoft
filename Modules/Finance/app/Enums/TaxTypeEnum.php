<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Iva = 'IVA';
    case Ice = 'ICE';
    case Isd = 'ISD';
    case Ir = 'IR';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Iva->value => self::Iva->value,
            self::Ice->value => self::Ice->value,
            self::Isd->value => self::Isd->value,
            self::Ir->value => self::Ir->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Iva->value,
            self::Ice->value,
            self::Isd->value,
            self::Ir->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Iva => __('IVA'),
            self::Ice => __('ICE'),
            self::Isd => __('ISD'),
            self::Ir => __('Income Tax'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Iva => 'success',
            self::Ice => 'warning',
            self::Isd => 'danger',
            self::Ir => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Iva => Heroicon::ShoppingCart,
            self::Ice => Heroicon::Fire,
            self::Isd => Heroicon::GlobeAlt,
            self::Ir => Heroicon::Banknotes,
        };
    }
}
