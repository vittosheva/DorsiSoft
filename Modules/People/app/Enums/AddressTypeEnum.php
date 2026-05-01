<?php

declare(strict_types=1);

namespace Modules\People\Enums;

use Filament\Support\Contracts\HasLabel;
use Modules\Core\Traits\EnumTrait;

enum AddressTypeEnum: string implements HasLabel
{
    use EnumTrait;

    case Main = 'main';
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Branch = 'branch';

    public static function default(): self
    {
        return self::Main;
    }

    public static function options(): array
    {
        return [
            self::Main->value => __('Main'),
            self::Billing->value => __('Billing'),
            self::Shipping->value => __('Shipping'),
            self::Branch->value => __('Branch'),
        ];
    }

    public static function values(): array
    {
        return [
            self::Main->value,
            self::Billing->value,
            self::Shipping->value,
            self::Branch->value,
        ];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Main => __('Main'),
            self::Billing => __('Billing'),
            self::Shipping => __('Shipping'),
            self::Branch => __('Branch'),
        };
    }
}
