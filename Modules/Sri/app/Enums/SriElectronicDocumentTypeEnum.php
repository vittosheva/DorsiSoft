<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipos de comprobantes electrónicos SRI Ecuador — catálogo completo.
 * Resolución NAC-DGERCGC14-00157.
 *
 * Nota: Modules\Sri\Enums\SriDocumentTypeEnum cubre solo Invoice/CreditNote/DebitNote
 * y se mantiene para compatibilidad con DocumentSequentialService.
 * Este enum es el catálogo canónico para generación de XML.
 */
enum SriElectronicDocumentTypeEnum: string implements HasLabel
{
    case Invoice = '01';
    case PurchaseSettlement = '03';
    case CreditNote = '04';
    case DebitNote = '05';
    case DeliveryGuide = '06';
    case Withholding = '07';

    public static function fromCode(string $code): self
    {
        return self::from($code);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoice => __('Invoice'),
            self::PurchaseSettlement => __('Purchase Settlement'),
            self::CreditNote => __('Credit Note'),
            self::DebitNote => __('Debit Note'),
            self::DeliveryGuide => __('Delivery Guide'),
            self::Withholding => __('Withholding'),
        };
    }

    public function getRootXmlElement(): string
    {
        return match ($this) {
            self::Invoice => 'factura',
            self::PurchaseSettlement => 'liquidacionCompra',
            self::CreditNote => 'notaCredito',
            self::DebitNote => 'notaDebito',
            self::DeliveryGuide => 'guiaRemision',
            self::Withholding => 'comprobanteRetencion',
        };
    }

    public function getXmlVersion(): string
    {
        return match ($this) {
            self::Invoice => '2.1.0',
            self::PurchaseSettlement => '1.1.0',
            self::CreditNote => '1.1.0',
            self::DebitNote => '1.0.0',
            self::DeliveryGuide => '1.1.0',
            self::Withholding => '2.0.0',
        };
    }

    public function getXsdFilename(): string
    {
        $version = $this->getXmlVersion();

        return match ($this) {
            self::Invoice => "factura_v{$version}.xsd",
            self::PurchaseSettlement => "liquidacionCompra_v{$version}.xsd",
            self::CreditNote => "notaCredito_v{$version}.xsd",
            self::DebitNote => "notaDebito_v{$version}.xsd",
            self::DeliveryGuide => "guiaRemision_v{$version}.xsd",
            self::Withholding => "comprobanteRetencion_v{$version}.xsd",
        };
    }
}
