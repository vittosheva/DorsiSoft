<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CollectionStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Voided = 'voided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Voided => __('Voided'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Voided => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Active => Heroicon::CheckCircle,
            self::Voided => Heroicon::XCircle,
        };
    }
}
