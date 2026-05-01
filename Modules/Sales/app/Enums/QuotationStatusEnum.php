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

enum QuotationStatusEnum: string implements DocumentStatus, HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Sent => __('Sent'),
            self::Accepted => __('Accepted'),
            self::Rejected => __('Rejected'),
            self::Expired => __('Expired'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Draft => Heroicon::Pencil,
            self::Sent => Heroicon::PaperAirplane,
            self::Accepted => Heroicon::CheckCircle,
            self::Rejected => Heroicon::XCircle,
            self::Expired => Heroicon::Clock,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isVoided(): bool
    {
        return false;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Draft => in_array($newStatus, [self::Sent, self::Accepted, self::Rejected]),
            self::Sent => in_array($newStatus, [self::Accepted, self::Rejected, self::Expired]),
            default => false,
        };
    }
}
