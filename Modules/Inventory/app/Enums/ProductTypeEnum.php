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

enum ProductTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case Product = 'product';
    case Service = 'service';
    case Kit = 'kit';

    public static function default(): self
    {
        return self::Product;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Product->value => __('Product'),
            self::Service->value => __('Service'),
            self::Kit->value => __('Kit'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Product->value,
            self::Service->value,
            self::Kit->value,
        ];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Product => __('Product'),
            self::Service => __('Service'),
            self::Kit => __('Kit'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Product => 'primary',
            self::Service => 'warning',
            self::Kit => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Product => Heroicon::Cube,
            self::Service => Heroicon::Cog,
            self::Kit => Heroicon::Squares2x2,
        };
    }

    public function isProduct(): bool
    {
        return in_array($this, [self::Product, self::Kit]);
    }

    public function isService(): bool
    {
        return $this === self::Service;
    }

    public function isKit(): bool
    {
        return $this === self::Kit;
    }
}
