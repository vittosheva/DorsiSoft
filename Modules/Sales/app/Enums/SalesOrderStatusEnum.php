<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Modules\Core\Contracts\DocumentStatus;

enum SalesOrderStatusEnum: string implements DocumentStatus, HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case PartiallyInvoiced = 'partially_invoiced';
    case FullyInvoiced = 'fully_invoiced';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Confirmed => __('Confirmed'),
            self::PartiallyInvoiced => __('Partially Invoiced'),
            self::FullyInvoiced => __('Fully Invoiced'),
            self::Cancelled => __('Cancelled'),
            self::Completed => __('Completed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::PartiallyInvoiced => 'primary',
            self::FullyInvoiced => 'info',
            self::Cancelled => 'danger',
            self::Completed => 'gray',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Pending;
    }

    /** SalesOrder no tiene estado Voided — se cancela, no se anula. */
    public function isVoided(): bool
    {
        return false;
    }

    public function isInvoiceable(): bool
    {
        return in_array($this, [self::Confirmed, self::PartiallyInvoiced], true);
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Pending => in_array($newStatus, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($newStatus, [self::PartiallyInvoiced, self::FullyInvoiced, self::Cancelled], true),
            self::PartiallyInvoiced => in_array($newStatus, [self::FullyInvoiced, self::Cancelled], true),
            self::FullyInvoiced => $newStatus === self::Completed,
            default => false,
        };
    }
}
