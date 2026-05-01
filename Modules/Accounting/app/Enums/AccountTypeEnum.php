<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AccountTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

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
            self::Asset => __('Asset'),
            self::Liability => __('Liability'),
            self::Equity => __('Equity'),
            self::Income => __('Income'),
            self::Expense => __('Expense'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Asset => 'info',
            self::Liability => 'danger',
            self::Equity => 'warning',
            self::Income => 'success',
            self::Expense => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Asset => Heroicon::OutlinedCube,
            self::Liability => Heroicon::OutlinedShieldCheck,
            self::Equity => Heroicon::OutlinedChartBar,
            self::Income => Heroicon::OutlinedCurrencyDollar,
            self::Expense => Heroicon::OutlinedCurrencyDollar,
        };
    }

    public function normalNature(): AccountNatureEnum
    {
        return match ($this) {
            self::Asset, self::Expense => AccountNatureEnum::Debit,
            self::Liability, self::Equity, self::Income => AccountNatureEnum::Credit,
        };
    }
}
