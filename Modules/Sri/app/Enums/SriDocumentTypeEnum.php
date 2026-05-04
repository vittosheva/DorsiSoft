<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\Withholding;

/**
 * Tipos de comprobantes electrónicos SRI Ecuador.
 * Catálogo oficial Resolución NAC-DGERCGC14-00157.
 */
enum SriDocumentTypeEnum: string implements HasDescription, HasIcon, HasLabel
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Withholding = 'withholding';
    case DeliveryGuide = 'delivery_guide';
    case PurchaseSettlement = 'purchase_settlement';

    public static function fromSriCode(string $code): ?self
    {
        return match ($code) {
            '01' => self::Invoice,
            '04' => self::CreditNote,
            '05' => self::DebitNote,
            '07' => self::Withholding,
            '06' => self::DeliveryGuide,
            '03' => self::PurchaseSettlement,
            default => null,
        };
    }

    public static function fromSriDocument(string $document): ?self
    {
        return match ($document) {
            self::Invoice => '01',
            self::CreditNote => '04',
            self::DebitNote => '05',
            self::Withholding => '07',
            self::DeliveryGuide => '06',
            self::PurchaseSettlement => '03',
            default => null,
        };
    }

    public static function getOptions(): array
    {
        return [
            self::Invoice->value => self::Invoice->getLabel(),
            self::CreditNote->value => self::CreditNote->getLabel(),
            self::DebitNote->value => self::DebitNote->getLabel(),
            self::Withholding->value => self::Withholding->getLabel(),
            self::DeliveryGuide->value => self::DeliveryGuide->getLabel(),
            self::PurchaseSettlement->value => self::PurchaseSettlement->getLabel(),
        ];
    }

    public static function getOptionsWithPrefix(): array
    {
        return [
            self::Invoice->value => str(self::Invoice->getLabel())->append(' ('.self::Invoice->sriCode().')')->toString(),
            self::CreditNote->value => str(self::CreditNote->getLabel())->append(' ('.self::CreditNote->sriCode().')')->toString(),
            self::DebitNote->value => str(self::DebitNote->getLabel())->append(' ('.self::DebitNote->sriCode().')')->toString(),
            self::Withholding->value => str(self::Withholding->getLabel())->append(' ('.self::Withholding->sriCode().')')->toString(),
            self::DeliveryGuide->value => str(self::DeliveryGuide->getLabel())->append(' ('.self::DeliveryGuide->sriCode().')')->toString(),
            self::PurchaseSettlement->value => str(self::PurchaseSettlement->getLabel())->append(' ('.self::PurchaseSettlement->sriCode().')')->toString(),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoice => __('Invoice'),
            self::CreditNote => __('Credit Note'),
            self::DebitNote => __('Debit Note'),
            self::Withholding => __('Withholding'),
            self::DeliveryGuide => __('Delivery Guide'),
            self::PurchaseSettlement => __('Purchase Settlement'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Invoice => InvoiceResource::getNavigationIcon(),
            self::CreditNote => CreditNoteResource::getNavigationIcon(),
            self::DebitNote => DebitNoteResource::getNavigationIcon(),
            self::Withholding => WithholdingResource::getNavigationIcon(),
            self::DeliveryGuide => DeliveryGuideResource::getNavigationIcon(),
            self::PurchaseSettlement => PurchaseSettlementResource::getNavigationIcon(),
        };
    }

    public function sriCode(): string
    {
        return match ($this) {
            self::Invoice => '01',
            self::CreditNote => '04',
            self::DebitNote => '05',
            self::Withholding => '07',
            self::DeliveryGuide => '06',
            self::PurchaseSettlement => '03',
        };
    }

    public function getFilamentResource(): string
    {
        return match ($this) {
            self::Invoice => InvoiceResource::class,
            self::CreditNote => CreditNoteResource::class,
            self::DebitNote => DebitNoteResource::class,
            self::Withholding => WithholdingResource::class,
            self::DeliveryGuide => DeliveryGuideResource::class,
            self::PurchaseSettlement => PurchaseSettlementResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::Invoice => Invoice::class,
            self::CreditNote => CreditNote::class,
            self::DebitNote => DebitNote::class,
            self::Withholding => Withholding::class,
            self::DeliveryGuide => DeliveryGuide::class,
            self::PurchaseSettlement => PurchaseSettlement::class,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Invoice => __('Invoice description'),
            self::CreditNote => __('Credit Note description'),
            self::DebitNote => __('Debit Note description'),
            self::Withholding => __('Withholding description'),
            self::DeliveryGuide => __('Delivery Guide description'),
            self::PurchaseSettlement => __('Purchase Settlement description'),
        };
    }

    public function getOrder(): string
    {
        return match ($this) {
            self::Invoice => '1',
            self::CreditNote => '3',
            self::DebitNote => '4',
            self::Withholding => '5',
            self::DeliveryGuide => '6',
            self::PurchaseSettlement => '7',
        };
    }

    public function sriName(): string
    {
        return match ($this) {
            self::Invoice => 'Factura',
            self::PurchaseSettlement => 'Liquidación de Compra de Bienes y Prestación de Servicios',
            self::CreditNote => 'Nota de Crédito',
            self::DebitNote => 'Nota de Débito',
            self::DeliveryGuide => 'Guía de Remisión',
            self::Withholding => 'Comprobante de Retención',
        };
    }

    public function sriDescription(): string
    {
        return match ($this) {
            self::Invoice => 'Factura de venta',
            self::PurchaseSettlement => 'Liquidación de compra',
            self::CreditNote => 'Nota de crédito',
            self::DebitNote => 'Nota de débito',
            self::DeliveryGuide => 'Guía de remisión',
            self::Withholding => 'Comprobante de retención',
        };
    }

    public function sriCatalogSortOrder(): int
    {
        return match ($this) {
            self::Invoice => 1,
            self::PurchaseSettlement => 2,
            self::CreditNote => 3,
            self::DebitNote => 4,
            self::DeliveryGuide => 5,
            self::Withholding => 6,
        };
    }
}
