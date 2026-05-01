<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Datos del receptor (cliente/proveedor) para el bloque de info del comprobante. */
final readonly class SriRecipientData
{
    public function __construct(
        public string $tipoIdentificacion, // '04'=RUC, '05'=cédula, '06'=pasaporte, '07'=consumidor final
        public string $identificacion,
        public string $razonSocial,
        public ?string $direccion = null,
        public ?string $email = null,
        public ?string $telefono = null,
    ) {}
}
