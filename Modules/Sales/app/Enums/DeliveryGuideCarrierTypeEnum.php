<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DeliveryGuideCarrierTypeEnum: string implements HasIcon, HasLabel
{
    case Own = 'own';
    case ThirdParty = 'third_party';

    public function getLabel(): string
    {
        return match ($this) {
            self::Own => __('Own carrier'),
            self::ThirdParty => __('External carrier'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Own => Heroicon::Truck,
            self::ThirdParty => Heroicon::UserGroup,
        };
    }
}
