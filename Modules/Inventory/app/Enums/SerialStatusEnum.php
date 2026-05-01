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

enum SerialStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Returned = 'returned';
    case Scrapped = 'scrapped';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Available => __('Available'),
            self::Reserved => __('Reserved'),
            self::Sold => __('Sold'),
            self::Returned => __('Returned'),
            self::Scrapped => __('Scrapped'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Available => 'success',
            self::Reserved => 'warning',
            self::Sold => 'primary',
            self::Returned => 'info',
            self::Scrapped => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Available => Heroicon::CheckCircle,
            self::Reserved => Heroicon::LockClosed,
            self::Sold => Heroicon::ShoppingCart,
            self::Returned => Heroicon::ArrowUturnLeft,
            self::Scrapped => Heroicon::Trash,
        };
    }
}
