<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Traits\EnumTrait;

enum SriEnvironmentEnum: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use EnumTrait;

    case TEST = 'pruebas';
    case PRODUCTION = 'produccion';

    public const DEFAULT = self::TEST->value;

    public function getLabel(): ?string
    {
        return $this->translate();
    }

    public function translate(): string
    {
        return match ($this) {
            self::TEST => __('Test'),
            self::PRODUCTION => __('Production'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TEST => Color::Amber,
            self::PRODUCTION => Color::Green,
        };
    }

    public function getIcon(): BackedEnum|string|null
    {
        return match ($this) {
            self::TEST => Heroicon::Beaker,
            self::PRODUCTION => Heroicon::Fire,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::TEST => __('Electronic documents will be sent to test servers. No legal validity.'),
            self::PRODUCTION => __('Electronic documents will be sent to production servers. Documents will have legal validity.'),
        };
    }

    public function getBadgeText(): string
    {
        return match ($this) {
            self::TEST => __('🧪 TESTING SRI'),
            self::PRODUCTION => __('🚀 PRODUCTION SRI'),
        };
    }

    public function isTest(): bool
    {
        return $this === self::TEST;
    }

    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }
}
