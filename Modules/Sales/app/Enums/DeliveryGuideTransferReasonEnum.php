<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum DeliveryGuideTransferReasonEnum: string implements HasIcon, HasLabel
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case InterEstablishmentTransfer = 'inter_establishment_transfer';
    case Consignment = 'consignment';
    case Return = 'return';
    case Transformation = 'transformation';
    case Export = 'export';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sale => __('Sale'),
            self::Purchase => __('Purchase'),
            self::InterEstablishmentTransfer => __('Inter-establishment Transfer'),
            self::Consignment => __('Consignment'),
            self::Return => __('Return'),
            self::Transformation => __('Transformation'),
            self::Export => __('Export'),
            self::Other => __('Other'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Sale => Heroicon::ShoppingCart,
            self::Purchase => Heroicon::ShoppingBag,
            self::InterEstablishmentTransfer => Heroicon::ArrowsRightLeft,
            self::Consignment => Heroicon::ArchiveBox,
            self::Return => Heroicon::ArrowUturnLeft,
            self::Transformation => Heroicon::WrenchScrewdriver,
            self::Export => Heroicon::GlobeAlt,
            self::Other => Heroicon::EllipsisHorizontalCircle,
        };
    }
}
