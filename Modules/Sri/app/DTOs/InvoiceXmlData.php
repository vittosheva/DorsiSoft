<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos completos para generar el XML de una Factura (cod. 01).
 * Versión XSD: 2.1.0
 */
final readonly class InvoiceXmlData
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
        public string $fechaEmision,            // dd/MM/yyyy
        public string $totalSinImpuestos,
        public string $totalDescuento,
        public string $importeTotal,
        public string $moneda,                  // 'DOLAR'
        public string $propina,                 // '0.00'
        public array $totalesConImpuestos,
        public array $detalles,
        public array $pagos,
        public array $infoAdicional,
        public bool $obligadoContabilidad,
        public ?string $guiaRemision = null,
    ) {}
}
