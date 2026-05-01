<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxGroupEnum: string implements HasColor, HasIcon, HasLabel
{
    case Iva = 'IVA';
    case Renta = 'RENTA';
    case Ice = 'ICE';
    case Isd = 'ISD';
    case Otro = 'OTRO';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Iva->value => self::Iva->value,
            self::Renta->value => self::Renta->value,
            self::Ice->value => self::Ice->value,
            self::Isd->value => self::Isd->value,
            self::Otro->value => self::Otro->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Iva->value,
            self::Renta->value,
            self::Ice->value,
            self::Isd->value,
            self::Otro->value,
        ];
    }

    public static function withholdingOptions(): array
    {
        return [
            self::Iva->value => __('VAT (IVA)'),
            self::Renta->value => __('Income Tax (IR)'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Iva => __('IVA'),
            self::Renta => __('IR'),
            self::Ice => __('ICE'),
            self::Isd => __('ISD'),
            self::Otro => __('Other'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Iva => 'success',
            self::Renta => 'primary',
            self::Ice => 'warning',
            self::Isd => 'danger',
            self::Otro => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Iva => Heroicon::ShoppingCart,
            self::Renta => Heroicon::Banknotes,
            self::Ice => Heroicon::Fire,
            self::Isd => Heroicon::GlobeAlt,
            self::Otro => Heroicon::QuestionMarkCircle,
        };
    }
}
