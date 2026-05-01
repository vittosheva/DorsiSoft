<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum FiscalPeriodStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => __('Opened'),
            self::CLOSED => __('Closed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::OPEN => Heroicon::OutlinedLockOpen,
            self::CLOSED => Heroicon::OutlinedLockClosed,
        };
    }
}
