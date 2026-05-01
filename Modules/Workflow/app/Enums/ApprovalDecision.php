<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum ApprovalDecision: string implements HasIcon, HasLabel
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Pending = 'pending';
    case Open = 'open';

    public function getLabel(): string
    {
        return match ($this) {
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Pending => __('Pending'),
            self::Open => __('Open'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Pending => 'warning',
            self::Open => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Approved => Heroicon::CheckCircle,
            self::Rejected => Heroicon::XMark,
            self::Pending => Heroicon::Clock,
            self::Open => Heroicon::EllipsisHorizontalCircle,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Approved, self::Rejected => true,
            default => false,
        };
    }
}
