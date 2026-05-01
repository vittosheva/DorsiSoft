<?php

declare(strict_types=1);

namespace Modules\Sales\Contracts;

use Modules\Sales\Enums\DocumentTypeEnum;

/**
 * Contrato para documentos que emiten secuenciales SRI Ecuador.
 *
 * Implementado por: Invoice, CreditNote, DebitNote.
 * NO implementado por: Collection, SalesOrder (sin secuencial SRI).
 */
interface HasSriSequential
{
    /** Código SRI formateado: "001-001-000000042", o null si es borrador sin secuencial. */
    public function getSriSequentialCode(): ?string;

    /** Tipo de documento para el tracking de secuenciales. */
    public function getSriDocumentType(): DocumentTypeEnum;
}
