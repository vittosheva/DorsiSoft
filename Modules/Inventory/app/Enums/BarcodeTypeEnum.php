<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

use Modules\Core\Traits\EnumTrait;

enum BarcodeTypeEnum: string
{
    use EnumTrait;

    case Ean13 = 'EAN13';
    case Upc = 'UPC';
    case Code128 = 'CODE128';
    case Code39 = 'CODE39';
    case Qr = 'QR';

    public static function default(): self
    {
        return self::Ean13;
    }

    public static function options(): array
    {
        return [
            self::Ean13->value => self::Ean13->value,
            self::Upc->value => self::Upc->value,
            self::Code128->value => self::Code128->value,
            self::Code39->value => self::Code39->value,
            self::Qr->value => self::Qr->value,
        ];
    }

    public static function values(): array
    {
        return [
            self::Ean13->value,
            self::Upc->value,
            self::Code128->value,
            self::Code39->value,
            self::Qr->value,
        ];
    }
}
