<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Datos del emisor (empresa) para el bloque <infoTributaria>. */
final readonly class SriIssuerData
{
    public function __construct(
        public string $ruc,
        public string $razonSocial,
        public string $nombreComercial,
        public string $dirMatriz,
        public string $ambiente,         // '1' = pruebas, '2' = produccion
        public string $tipoEmision,      // '1' = normal
        public string $codDoc,           // '01', '04', etc.
        public string $estab,            // '001'
        public string $ptoEmi,           // '001'
        public string $secuencial,       // '000000001'
        public string $claveAcceso,      // 49-char key
        public string $dirEstablecimiento,
        public ?string $contribuyenteEspecial = null,
    ) {}
}
