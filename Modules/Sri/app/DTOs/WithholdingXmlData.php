<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos para generar el XML de un Comprobante de Retención (cod. 07).
 * Versión XSD: 2.0.0
 */
final readonly class WithholdingXmlData
{
    /**
     * @param  list<SriWithholdingItemData>  $impuestos
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    public function __construct(
        public SriIssuerData $issuer,
        public SriRecipientData $recipient,
        public string $fechaEmision,        // dd/MM/yyyy
        public string $periodoFiscal,       // MM/yyyy
        public bool $obligadoContabilidad,
        public array $impuestos,
        public array $infoAdicional,
    ) {}
}
