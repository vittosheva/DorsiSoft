<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Ítem de retención para la sección <impuestos> del comprobante de retención. */
final readonly class SriWithholdingItemData
{
    public function __construct(
        public string $codigo,              // '1'=IR, '2'=IVA
        public string $codigoRetencion,     // SRI retention code
        public string $baseImponible,
        public string $porcentajeRetener,
        public string $valorRetenido,
        public string $codDocSustento,      // '01', '03', etc.
        public string $numDocSustento,      // 001-001-000000001
        public string $fechaEmisionDocSustento, // dd/MM/yyyy
    ) {}
}
