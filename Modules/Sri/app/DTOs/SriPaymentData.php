<?php

declare(strict_types=1);

namespace Modules\Sri\DTOs;

/** Forma de pago para la sección <pagos>. */
final readonly class SriPaymentData
{
    public function __construct(
        public string $formaPago, // SRI code: '01', '16', '17', etc.
        public string $total,
        public string $plazo = '0',
        public string $unidadTiempo = 'dias',
    ) {}
}
