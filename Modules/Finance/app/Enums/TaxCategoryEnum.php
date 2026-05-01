<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum TaxCategoryEnum: string implements HasColor, HasIcon, HasLabel
{
    case Taxable = 'taxable';     // IVA normal (0, 5, 15)
    case Exempt = 'exempt';       // Exento
    case NonObject = 'non_object'; // No objeto
    case Special = 'special';     // Casos raros

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Taxable->value => self::Taxable->value,
            self::Exempt->value => self::Exempt->value,
            self::NonObject->value => self::NonObject->value,
            self::Special->value => self::Special->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Taxable->value,
            self::Exempt->value,
            self::NonObject->value,
            self::Special->value,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Taxable => __('Taxable'),
            self::Exempt => __('Exempt'),
            self::NonObject => __('Non Object'),
            self::Special => __('Special'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Taxable => 'success',
            self::Exempt => 'warning',
            self::NonObject => 'danger',
            self::Special => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Taxable => Heroicon::Check,
            self::Exempt => Heroicon::XMark,
            self::NonObject => Heroicon::Minus,
            self::Special => Heroicon::Star,
        };
    }
}
