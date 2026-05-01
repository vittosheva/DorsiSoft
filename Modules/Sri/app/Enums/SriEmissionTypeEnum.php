<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Tipo de emisión SRI Ecuador.
 * En la práctica solo se utiliza Normal (1) para emisión offline.
 */
enum SriEmissionTypeEnum: string implements HasIcon, HasLabel
{
    case Normal = '1';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => __('Normal (offline)'),
        };
    }

    /** Código numérico SRI para el XML (campo <tipoEmision>). */
    public function sriCode(): string
    {
        return $this->value;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Normal => Heroicon::DocumentText,
        };
    }
}
