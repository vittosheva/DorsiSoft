<?php

declare(strict_types=1);

namespace Modules\Sri\Services\Xml\Mappers\Concerns;

trait BuildsRecipientData
{
    /**
     * Converts internal identification type keys to SRI-required 2-digit codes.
     *
     * Internal values (IdentificationTypeSelect):
     *   'ruc'      → '04'  (RUC)
     *   'cedula'   → '05'  (Cédula de identidad)
     *   'passport' → '06'  (Pasaporte)
     *   'other'    → '07'  (Consumidor Final / exterior)
     *
     * XSD pattern: [0][4-8]
     */
    private function toSriIdentificationTypeCode(?string $type, string $fallback = '07'): string
    {
        return match ($type) {
            'ruc' => '04',
            'cedula' => '05',
            'passport' => '06',
            'other' => '07',
            // Already a valid SRI code (04–08): pass through
            '04', '05', '06', '07', '08' => $type,
            default => $fallback,
        };
    }
}
