<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Contrato base para todas las entidades lógicas de documento ERP.
 *
 * Implementado por: Invoice, CreditNote, DebitNote, Payment, SalesOrder.
 * Permite código genérico que opere sobre cualquier tipo de documento
 * sin acoplarse a un modelo concreto.
 *
 * Nota: getSriSequentialCode() NO está aquí. Solo los documentos SRI
 * implementan HasSriSequential (Invoice, CreditNote, DebitNote).
 * Payment y SalesOrder no tienen secuencial SRI.
 */
interface DocumentContract
{
    /** Código interno del documento (ej: FAC-2026-000001). */
    public function getDocumentCode(): string;

    /** Si el documento puede ser editado en su estado actual. */
    public function isEditable(): bool;

    /** Si el documento ha sido anulado. */
    public function isVoided(): bool;

    /** company_id del tenant al que pertenece. */
    public function getCompanyId(): int;
}
