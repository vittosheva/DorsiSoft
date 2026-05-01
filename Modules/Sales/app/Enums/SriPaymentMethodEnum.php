<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Catálogo oficial SRI de formas de pago para documentos electrónicos.
 * Resolución NAC-DGERCGC18-00000233 y sus reformas.
 */
enum SriPaymentMethodEnum: string implements HasIcon, HasLabel
{
    // Solo los names correctos, sin duplicados
    case Cash = '01';
    case Compensation = '15';
    case DebitCard = '16';
    case ElectronicMoney = '17';
    case PrepaidCard = '18';
    case CreditCard = '19';
    case BankTransfer = '20';
    case Cheque = '20C';
    case EndorsedSecurities = '21';
    case Credit = 'CR';

    public static function default(): self
    {
        return self::Cash;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => $this->buildName(),
            self::Compensation => $this->buildName(),
            self::DebitCard => $this->buildName(),
            self::ElectronicMoney => $this->buildName(),
            self::PrepaidCard => $this->buildName(),
            self::CreditCard => $this->buildName(),
            self::BankTransfer => $this->buildName(),
            self::EndorsedSecurities => $this->buildName(),
            self::Cheque => $this->buildName(),
            self::Credit => $this->buildName(),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Cash => Heroicon::Banknotes,
            self::Compensation => Heroicon::ArrowsRightLeft,
            self::DebitCard => Heroicon::CreditCard,
            self::ElectronicMoney => Heroicon::DevicePhoneMobile,
            self::PrepaidCard => Heroicon::CreditCard,
            self::CreditCard => Heroicon::CreditCard,
            self::BankTransfer => Heroicon::BuildingLibrary,
            self::EndorsedSecurities => Heroicon::DocumentText,
            self::Cheque => Heroicon::DocumentCheck,
            self::Credit => Heroicon::Clock,
        };
    }

    public function buildName(): string
    {
        return $this->value.' - '.__($this->name);
    }

    public function requiresBankAccount(): bool
    {
        return match ($this) {
            self::BankTransfer, self::Cheque => true,
            default => false,
        };
    }

    public function isCredit(): bool
    {
        return $this === self::Credit;
    }

    public function isCash(): bool
    {
        return $this === self::Cash;
    }
}
