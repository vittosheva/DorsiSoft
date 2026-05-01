<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum ElectronicStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case XmlGenerated = 'xml_generated';
    case Signed = 'signed';
    case Submitted = 'submitted';
    case Authorized = 'authorized';
    case Rejected = 'rejected';
    case Error = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::XmlGenerated => __('XML Generated'),
            self::Signed => __('Signed'),
            self::Submitted => __('Submitted to SRI'),
            self::Authorized => __('Authorized'),
            self::Rejected => __('Rejected'),
            self::Error => __('Error'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::XmlGenerated => 'info',
            self::Signed => 'info',
            self::Submitted => Color::Amber,
            self::Authorized => 'success',
            self::Rejected => 'danger',
            self::Error => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::XmlGenerated => Heroicon::Document,
            self::Signed => Heroicon::LockClosed,
            self::Submitted => Heroicon::PaperAirplane,
            self::Authorized => Heroicon::CheckCircle,
            self::Rejected => Heroicon::XMark,
            self::Error => Heroicon::ExclamationCircle,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Authorized, self::Rejected], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => $next === self::XmlGenerated,
            self::XmlGenerated => $next === self::Signed,
            self::Signed => $next === self::Submitted,
            self::Submitted => in_array($next, [self::Authorized, self::Rejected, self::Error], true),
            self::Error => $next === self::XmlGenerated, // allow retry from error
            default => false,
        };
    }
}
