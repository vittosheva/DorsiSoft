<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum ElectronicCorrectionStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case None = 'none';
    case Required = 'required';
    case InProgress = 'in_progress';
    case Superseded = 'superseded';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('No correction required'),
            self::Required => __('Correction required'),
            self::InProgress => __('Correction in progress'),
            self::Superseded => __('Superseded by correction'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::None => 'gray',
            self::Required => 'warning',
            self::InProgress => 'info',
            self::Superseded => 'success',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::None => Heroicon::Check,
            self::Required => Heroicon::ExclamationTriangle,
            self::InProgress => Heroicon::Clock,
            self::Superseded => Heroicon::CheckCircle,
        };
    }
}
