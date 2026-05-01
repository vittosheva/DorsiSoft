<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CollectionMethodEnum: string implements HasColor, HasIcon, HasLabel
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Check = 'check';
    case CreditCard = 'credit_card';
    case CreditNote = 'credit_note';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::BankTransfer => __('Bank Transfer'),
            self::Check => __('Check'),
            self::CreditCard => __('Credit Card'),
            self::CreditNote => __('Credit Note'),
            self::Other => __('Other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cash => 'success',
            self::BankTransfer => 'info',
            self::Check => 'warning',
            self::CreditCard => 'primary',
            self::CreditNote => 'gray',
            self::Other => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Cash => Heroicon::CurrencyDollar,
            self::BankTransfer => Heroicon::Banknotes,
            self::Check => Heroicon::Check,
            self::CreditCard => Heroicon::CreditCard,
            self::CreditNote => Heroicon::DocumentText,
            self::Other => Heroicon::QuestionMarkCircle,
        };
    }
}
