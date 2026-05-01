<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Official SRI reason catalog for credit notes.
 * Resolution NAC-DGERCGC14-00157 and subsequent amendments.
 */
enum CreditNoteReasonEnum: string implements HasDescription, HasLabel
{
    case ReturnOfGoods = '01';
    case VoidDocument = '02';
    case CommercialDiscount = '03';
    case PriceAdjustment = '04';
    case Other = '05';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReturnOfGoods => __('Return of goods'),
            self::VoidDocument => __('Void document'),
            self::CommercialDiscount => __('Commercial discount'),
            self::PriceAdjustment => __('Price adjustment'),
            self::Other => __('Other'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ReturnOfGoods => __('01 - Return of goods'),
            self::VoidDocument => __('02 - Void document'),
            self::CommercialDiscount => __('03 - Commercial discount'),
            self::PriceAdjustment => __('04 - Price adjustment'),
            self::Other => __('05 - Other'),
        };
    }
}
