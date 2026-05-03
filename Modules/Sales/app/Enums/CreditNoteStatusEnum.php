<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Modules\Core\Contracts\DocumentStatus;

enum CreditNoteStatusEnum: string implements DocumentStatus, HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingAuthorization = 'pending_authorization';
    case Issued = 'issued';
    case FullyApplied = 'fully_applied';
    case Voided = 'voided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::PendingAuthorization => __('Pending Authorization'),
            self::Issued => __('Issued'),
            self::FullyApplied => __('Fully Applied'),
            self::Voided => __('Voided'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingAuthorization => 'warning',
            self::Issued => 'success',
            self::FullyApplied => 'info',
            self::Voided => 'danger',
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
            self::Issued => in_array($newStatus, [self::FullyApplied, self::Voided], true),
            default => false,
        };
    }
}
