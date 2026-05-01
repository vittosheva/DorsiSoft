<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Traits\EnumTrait;

enum SriRegimeTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case GENERAL = 'general';
    case RIMPE_NEGOCIO_POPULAR = 'rimpe_negocio_popular';
    case RIMPE_EMPRENDEDOR = 'rimpe_emprendedor';

    public const DEFAULT = self::GENERAL->value;

    public function getLabel(): ?string
    {
        return $this->translate();
    }

    public function translate(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::RIMPE_NEGOCIO_POPULAR => 'RIMPE Negocio Popular',
            self::RIMPE_EMPRENDEDOR => 'RIMPE Emprendedor',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GENERAL => Color::Blue,
            self::RIMPE_NEGOCIO_POPULAR => Color::Green,
            self::RIMPE_EMPRENDEDOR => Color::Purple,
        };
    }

    public function getColorName(): string
    {
        return match ($this) {
            self::GENERAL => 'primary',
            self::RIMPE_NEGOCIO_POPULAR => 'success',
            self::RIMPE_EMPRENDEDOR => 'warning',
        };
    }

    public function getIcon(): BackedEnum|string|null
    {
        return match ($this) {
            self::GENERAL => Heroicon::BuildingOffice,
            self::RIMPE_NEGOCIO_POPULAR => Heroicon::BuildingStorefront,
            self::RIMPE_EMPRENDEDOR => Heroicon::CheckBadge,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GENERAL => __('General tax regime for standard companies'),
            self::RIMPE_NEGOCIO_POPULAR => __('Simplified regime for popular businesses'),
            self::RIMPE_EMPRENDEDOR => __('Simplified regime for entrepreneurs'),
        };
    }

    public function getMaxIncome(): ?float
    {
        return match ($this) {
            self::GENERAL => null, // No limit
            self::RIMPE_NEGOCIO_POPULAR => 20000.00,
            self::RIMPE_EMPRENDEDOR => 300000.00,
        };
    }

    public function requiresAccountingBooks(): bool
    {
        return match ($this) {
            self::GENERAL => true,
            self::RIMPE_NEGOCIO_POPULAR => false,
            self::RIMPE_EMPRENDEDOR => false,
        };
    }

    public function getValidTaxRates(): array
    {
        return match ($this) {
            self::GENERAL => [0, 12, 15],
            self::RIMPE_NEGOCIO_POPULAR => [0],
            self::RIMPE_EMPRENDEDOR => [0, 5],
        };
    }

    public function isRimpe(): bool
    {
        return in_array($this, [self::RIMPE_NEGOCIO_POPULAR, self::RIMPE_EMPRENDEDOR]);
    }

    public function getRequiredDocuments(): array
    {
        return match ($this) {
            self::GENERAL => [
                __('RUC'),
                __('Financial statements'),
                __('Tax declarations'),
                __('Accounting books'),
            ],
            self::RIMPE_NEGOCIO_POPULAR => [
                __('RUC or ID'),
                __('Simple sales record'),
            ],
            self::RIMPE_EMPRENDEDOR => [
                __('RUC'),
                __('Income records'),
                __('Basic tax forms'),
            ],
        };
    }

    public function getTaxObligations(): array
    {
        return match ($this) {
            self::GENERAL => [
                __('Monthly IVA declaration'),
                __('Annual income tax'),
                __('Withholding tax'),
                __('Municipal patents'),
            ],
            self::RIMPE_NEGOCIO_POPULAR => [
                __('Biannual simplified declaration'),
            ],
            self::RIMPE_EMPRENDEDOR => [
                __('Simplified quarterly declaration'),
                __('Annual income summary'),
            ],
        };
    }
}
