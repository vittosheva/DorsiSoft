<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos para generar el XML de una Liquidación de Compra (cod. 03).
 * Versión XSD: 1.1.0
 */
final readonly class PurchaseSettlementXmlData
{
    /**
     * @param  list<SriInvoiceItemData>  $detalles
     * @param  list<SriTaxLineData>  $totalesConImpuestos
     * @param  list<SriPaymentData>  $pagos
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    public function __construct(
        public SriIssuerData $issuer,
        public SriRecipientData $recipient,
        public string $fechaEmision,
        public string $totalSinImpuestos,
        public string $totalDescuento,
        public string $importeTotal,
        public string $moneda,
        public array $totalesConImpuestos,
        public array $detalles,
        public array $pagos,
        public array $infoAdicional,
        public bool $obligadoContabilidad,
    ) {}
}
