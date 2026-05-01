<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum JournalEntryStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Voided = 'voided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Approved => __('Approved'),
            self::Voided => __('Voided'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Approved => 'success',
            self::Voided => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Draft => Heroicon::PencilSquare,
            self::Approved => Heroicon::CheckCircle,
            self::Voided => Heroicon::XCircle,
        };
    }
}
