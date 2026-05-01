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

enum MovementTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::In => __('Entry'),
            self::Out => __('Exit'),
            self::Transfer => __('Transfer'),
            self::Adjustment => __('Adjustment'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'danger',
            self::Transfer => 'info',
            self::Adjustment => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::In => Heroicon::ArrowDownTray,
            self::Out => Heroicon::ArrowUpTray,
            self::Transfer => Heroicon::ArrowsRightLeft,
            self::Adjustment => Heroicon::AdjustmentsHorizontal,
        };
    }

    public function isIn(): bool
    {
        return $this === self::In;
    }

    public function isOut(): bool
    {
        return $this === self::Out;
    }

    public function isTransfer(): bool
    {
        return $this === self::Transfer;
    }

    public function isAdjustment(): bool
    {
        return $this === self::Adjustment;
    }
}
