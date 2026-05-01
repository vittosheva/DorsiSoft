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

enum AbcClassificationEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case A = 'A';
    case B = 'B';
    case C = 'C';
    case X = 'X';

    public static function default(): self
    {
        return self::C;
    }

    public static function options(): array
    {
        return [
            self::A->value => __('A — High value'),
            self::B->value => __('B — Medium value'),
            self::C->value => __('C — Low value'),
            self::X->value => __('X — Obsolete'),
        ];
    }

    public static function values(): array
    {
        return [
            self::A->value,
            self::B->value,
            self::C->value,
            self::X->value,
        ];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::A => __('A — High value'),
            self::B => __('B — Medium value'),
            self::C => __('C — Low value'),
            self::X => __('X — Obsolete'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::A => 'success',
            self::B => 'info',
            self::C => 'warning',
            self::X => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::A => Heroicon::Star,
            self::B => Heroicon::AdjustmentsHorizontal,
            self::C => Heroicon::Bars3BottomLeft,
            self::X => Heroicon::QuestionMarkCircle,
        };
    }
}
