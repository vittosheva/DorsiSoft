<?php

declare(strict_types=1);

namespace Modules\People\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Core\Traits\EnumTrait;

enum BankAccountTypeEnum: string implements HasIcon, HasLabel
{
    use EnumTrait;

    case Ahorros = 'ahorros';
    case Corriente = 'corriente';

    public static function default(): self
    {
        return self::Ahorros;
    }

    public static function options(): array
    {
        return [
            self::Ahorros->value => __('Savings'),
            self::Corriente->value => __('Checking'),
        ];
    }

    public static function values(): array
    {
        return [
            self::Ahorros->value,
            self::Corriente->value,
        ];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Ahorros => __('Savings'),
            self::Corriente => __('Checking'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Ahorros => Heroicon::Banknotes,
            self::Corriente => Heroicon::BuildingStorefront,
        };
    }
}
