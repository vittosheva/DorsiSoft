<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos para generar el XML de una Nota de Crédito (cod. 04).
 * Versión XSD: 1.1.0
 */
final readonly class CreditNoteXmlData
{
    /**
     * @param  list<SriInvoiceItemData>  $detalles
     * @param  list<SriTaxLineData>  $totalesConImpuestos
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    public function __construct(
        public SriIssuerData $issuer,
        public SriRecipientData $recipient,
        public string $fechaEmision,            // dd/MM/yyyy
        public string $fechaEmisionDocSustento, // dd/MM/yyyy
        public string $numDocSustento,          // 001-001-000000001
        public string $codDocSustento,          // '01' = factura
        public string $reason,
        public string $totalSinImpuestos,
        public string $valorModificacion,
        public string $moneda,
        public bool $obligadoContabilidad,
        public array $totalesConImpuestos,
        public array $detalles,
        public array $infoAdicional,
        public ?string $rise = null,
    ) {}
}
