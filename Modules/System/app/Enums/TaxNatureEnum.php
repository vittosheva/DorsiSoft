<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxNatureEnum: string implements HasColor, HasIcon, HasLabel
{
    case Impuesto = 'IMPUESTO';
    case Retencion = 'RETENCION';
    case Percepcion = 'PERCEPCION';
    case Contribucion = 'CONTRIBUCION';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Impuesto->value => self::Impuesto->getLabel(),
            self::Retencion->value => self::Retencion->getLabel(),
            self::Percepcion->value => self::Percepcion->getLabel(),
            self::Contribucion->value => self::Contribucion->getLabel(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Impuesto->value,
            self::Retencion->value,
            self::Percepcion->value,
            self::Contribucion->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Impuesto => __('Tax'),
            self::Retencion => __('Withholding'),
            self::Percepcion => __('Perception'),
            self::Contribucion => __('Contribution'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Impuesto => 'primary',
            self::Retencion => 'warning',
            self::Percepcion => 'info',
            self::Contribucion => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Impuesto => Heroicon::Banknotes,
            self::Retencion => Heroicon::ArrowDownTray,
            self::Percepcion => Heroicon::ArrowUpTray,
            self::Contribucion => Heroicon::BuildingOffice,
        };
    }
}
