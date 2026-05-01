<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Traits\EnumTrait;

enum LanguageEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case ES = 'es';
    case EN = 'en';

    public const DEFAULT = self::ES->value;

    public function getLabel(): ?string
    {
        return $this->translate();
    }

    public function translate(): string
    {
        return match ($this) {
            self::ES => __('Spanish'),
            self::EN => __('English'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ES => Color::Blue,
            self::EN => Color::Green,
        };
    }

    public function getIcon(): BackedEnum|string|null
    {
        return match ($this) {
            self::ES => Heroicon::Language,
            self::EN => Heroicon::Language,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ES => __('Spanish language'),
            self::EN => __('English language'),
        };
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function getLocale(): string
    {
        return $this->value;
    }
}
