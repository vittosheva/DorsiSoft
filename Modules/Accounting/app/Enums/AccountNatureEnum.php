<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AccountNatureEnum: string implements HasColor, HasIcon, HasLabel
{
    case Debit = 'debit';
    case Credit = 'credit';

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case): string => $case->getLabel(), self::cases()),
        );
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Debit => __('Debit'),
            self::Credit => __('Credit'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Debit => 'success',
            self::Credit => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Debit => Heroicon::OutlinedArrowDown,
            self::Credit => Heroicon::OutlinedArrowUp,
        };
    }

    public function opposite(): self
    {
        return match ($this) {
            self::Debit => self::Credit,
            self::Credit => self::Debit,
        };
    }
}
