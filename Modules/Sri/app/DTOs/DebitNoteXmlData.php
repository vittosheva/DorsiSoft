<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/**
 * Datos para generar el XML de una Nota de Débito (cod. 05).
 * Versión XSD: 1.0.0
 */
final readonly class DebitNoteXmlData
{
    /**
     * @param  list<DebitNoteReason>  $reasons
     * @param  list<SriTaxLineData>  $impuestos
     * @param  list<SriPaymentData>  $pagos
     * @param  list<SriAdditionalInfoData>  $infoAdicional
     */
    public function __construct(
        public SriIssuerData $issuer,
        public SriRecipientData $recipient,
        public string $fechaEmision,
        public string $fechaEmisionDocSustento,
        public string $numDocSustento,
        public string $codDocSustento,
        public string $totalSinImpuestos,
        public string $importeTotal,
        public string $moneda,
        public array $reasons,
        public array $impuestos,
        public array $pagos,
        public array $infoAdicional,
    ) {}
}
