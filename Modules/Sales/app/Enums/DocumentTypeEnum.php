<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;
use Modules\Sri\Enums\SriDocumentTypeEnum;

/**
 * Catálogo integral de todos los tipos de documento ERP.
 *
 * IMPORTANTE: Este enum NO reemplaza SriDocumentTypeEnum.
 * SriDocumentTypeEnum maneja los valores de la columna `document_type`
 * en sales_document_sequences (y debe mantenerse estable por la DB).
 * Este enum cubre TODOS los tipos para routing, permisos y feature flags.
 *
 * Para añadir un nuevo tipo de documento:
 *   1. Añadir un case aquí.
 *   2. Si requiere SRI: añadir en SriDocumentTypeEnum y actualizar sriDocumentType().
 *   3. Crear modelo, migración, enum de estado y recurso Filament en el módulo correspondiente.
 */
enum DocumentTypeEnum: string implements HasIcon, HasLabel
{
    // Comprobantes electrónicos SRI
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case PurchaseSettlement = 'purchase_settlement';
    case Withholding = 'withholding';
    case DeliveryGuide = 'delivery_guide';

    // Documentos internos (sin secuencial SRI)
    case SalesOrder = 'sales_order';

    case Collection = 'collection';
    case JournalEntry = 'journal_entry';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoice => __('Invoice'),
            self::CreditNote => __('Credit Note'),
            self::DebitNote => __('Debit Note'),
            self::PurchaseSettlement => __('Purchase Settlement'),
            self::Withholding => __('Withholding'),
            self::DeliveryGuide => __('Delivery Guide'),
            self::SalesOrder => __('Sales order'),
            self::Collection => __('Collection'),
            self::JournalEntry => __('Journal Entry'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Invoice => InvoiceResource::getNavigationIcon(),
            self::CreditNote => CreditNoteResource::getNavigationIcon(),
            self::DebitNote => DebitNoteResource::getNavigationIcon(),
            self::PurchaseSettlement => PurchaseSettlementResource::getNavigationIcon(),
            self::Withholding => WithholdingResource::getNavigationIcon(),
            self::DeliveryGuide => DeliveryGuideResource::getNavigationIcon(),
            self::SalesOrder => SalesOrderResource::getNavigationIcon(),
            self::Collection => CollectionResource::getNavigationIcon(),
            self::JournalEntry => Heroicon::DocumentText,
        };
    }

    /** ¿Este tipo de documento requiere secuencial SRI (establecimiento + punto + secuencial)? */
    public function hasSriSequential(): bool
    {
        return match ($this) {
            self::Invoice, self::CreditNote, self::DebitNote, self::PurchaseSettlement,
            self::Withholding, self::DeliveryGuide => true,
            default => false,
        };
    }

    /** ¿Este tipo pasa por el flujo de aprobaciones? */
    public function hasApproval(): bool
    {
        return match ($this) {
            self::Invoice, self::CreditNote, self::Collection, self::PurchaseSettlement,
            self::Withholding => true,
            default => false,
        };
    }

    /** ¿Este tipo tiene líneas de detalle (items)? */
    public function hasItems(): bool
    {
        return match ($this) {
            self::Invoice, self::CreditNote, self::SalesOrder, self::PurchaseSettlement,
            self::Withholding, self::DeliveryGuide => true,
            default => false,
        };
    }

    /** Clave de ruta URL (plural, kebab-case). */
    public function routeKey(): string
    {
        return match ($this) {
            self::Invoice => 'invoices',
            self::CreditNote => 'credit-notes',
            self::DebitNote => 'debit-notes',
            self::PurchaseSettlement => 'purchase-settlements',
            self::Withholding => 'withholdings',
            self::DeliveryGuide => 'delivery-guides',
            self::SalesOrder => 'sales-orders',
            self::Collection => 'collections',
            self::JournalEntry => 'journal-entries',
        };
    }

    /**
     * Retorna el SriDocumentTypeEnum correspondiente para tipos con secuencial SRI.
     * Retorna null para tipos sin secuencial SRI (Collection, SalesOrder, etc.).
     */
    public function sriDocumentType(): ?SriDocumentTypeEnum
    {
        return match ($this) {
            self::Invoice => SriDocumentTypeEnum::Invoice,
            self::CreditNote => SriDocumentTypeEnum::CreditNote,
            self::DebitNote => SriDocumentTypeEnum::DebitNote,
            self::PurchaseSettlement => SriDocumentTypeEnum::PurchaseSettlement,
            self::Withholding => SriDocumentTypeEnum::Withholding,
            self::DeliveryGuide => SriDocumentTypeEnum::DeliveryGuide,
            default => null,
        };
    }
}
