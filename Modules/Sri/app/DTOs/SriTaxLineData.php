<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Línea de impuesto para <totalConImpuestos> o <impuestos> de un detalle. */
final readonly class SriTaxLineData
{
    public function __construct(
        public string $codigo,            // '2' = IVA, '3' = ICE, '5' = IRBPNR
        public string $codigoPorcentaje,  // '0'=0%, '2'=12%, '3'=14%, '4'=15%, '6'=5%
        public string $tarifa,            // '0.00', '12.00', etc.
        public string $baseImponible,
        public string $valor,
    ) {}
}
