<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Core\Traits\EnumTrait;

enum ReservationStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Fulfilled = 'fulfilled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Confirmed => __('Confirmed'),
            self::Cancelled => __('Cancelled'),
            self::Fulfilled => __('Fulfilled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Cancelled => 'danger',
            self::Fulfilled => 'success',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Confirmed => Heroicon::CheckBadge,
            self::Cancelled => Heroicon::XCircle,
            self::Fulfilled => Heroicon::CheckCircle,
        };
    }
}
