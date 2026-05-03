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

enum WithholdingStatusEnum: string implements DocumentStatus, HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case PendingAuthorization = 'pending_authorization';
    case Issued = 'issued';
    case Voided = 'voided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::PendingAuthorization => __('Pending Authorization'),
            self::Issued => __('Issued'),
            self::Voided => __('Voided'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingAuthorization => 'warning',
            self::Issued => 'success',
            self::Voided => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencil,
            self::PendingAuthorization => Heroicon::OutlinedClock,
            self::Issued => Heroicon::OutlinedCheck,
            self::Voided => Heroicon::OutlinedXCircle,
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
            self::Draft => $newStatus === self::PendingAuthorization,
            self::PendingAuthorization => in_array($newStatus, [self::Issued, self::Voided], true),
            self::Issued => in_array($newStatus, [self::Voided], true),
            default => false,
        };
    }
}
