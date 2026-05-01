<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

trait HasActiveIcon
{
    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        $icon = static::getNavigationIcon();

        if (is_string($icon)) {
            return str($icon)
                ->replace('heroicon-o', 'heroicon-s')
                ->toString();
        }

        $name = str($icon->name)->replace('Outlined', '')->toString();

        return Heroicon::{$name};
    }
}
