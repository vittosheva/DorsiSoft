<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Core\Contracts\DocumentStatus;

enum SaleNoteStatusEnum: string implements DocumentStatus, HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Voided = 'voided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Issued => __('Issued'),
            self::Voided => __('Voided'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'success',
            self::Voided => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Draft => Heroicon::Pencil,
            self::Issued => Heroicon::CheckCircle,
            self::Voided => Heroicon::XCircle,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isVoided(): bool
    {
        return $this === self::Voided;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Draft => in_array($newStatus, [self::Issued, self::Voided]),
            self::Issued => in_array($newStatus, [self::Voided]),
            default => false,
        };
    }
}
