<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum WithholdingAppliesToEnum: string implements HasColor, HasIcon, HasLabel
{
    case Bien = 'BIEN';
    case Servicio = 'SERVICIO';
    case Ambos = 'AMBOS';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Bien->value => self::Bien->getLabel(),
            self::Servicio->value => self::Servicio->getLabel(),
            self::Ambos->value => self::Ambos->getLabel(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Bien->value,
            self::Servicio->value,
            self::Ambos->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Bien => __('Goods'),
            self::Servicio => __('Services'),
            self::Ambos => __('Both'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bien => 'primary',
            self::Servicio => 'info',
            self::Ambos => 'success',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Bien => Heroicon::Cube,
            self::Servicio => Heroicon::Cog,
            self::Ambos => Heroicon::Squares2x2,
        };
    }
}
