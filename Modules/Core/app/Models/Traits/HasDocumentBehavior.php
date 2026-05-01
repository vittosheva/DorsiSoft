<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Modules\Core\Contracts\DocumentStatus;

/**
 * Implementación default del contrato DocumentContract para modelos Eloquent.
 *
 * Delega isEditable() e isVoided() al enum de estado cuando este implementa
 * DocumentStatus. Para modelos sin columna `status` (ej: Payment), usa
 * voided_at como indicador de anulación.
 */
trait HasDocumentBehavior
{
    public function getDocumentCode(): string
    {
        return (string) $this->code;
    }

    public function getCompanyId(): int
    {
        return (int) $this->company_id;
    }

    public function isEditable(): bool
    {
        $status = $this->status ?? null;

        if ($status instanceof DocumentStatus) {
            return $status->isEditable();
        }

        // Fallback para modelos sin columna status (Payment usa voided_at)
        return ! isset($this->voided_at) || $this->voided_at === null;
    }

    /**
     * Removes cached PDF metadata so the next request triggers regeneration.
     * Call this after updating snapshot fields that affect PDF content.
     */
    public function clearPdfMetadata(): void
    {
        if (! is_array($this->metadata)) {
            return;
        }

        $this->metadata = array_diff_key($this->metadata, array_flip(['pdf_path', 'pdf_disk', 'pdf_generated_at']));
    }

    public function isVoided(): bool
    {
        $status = $this->status ?? null;

        if ($status instanceof DocumentStatus) {
            return $status->isVoided();
        }

        // Fallback para modelos sin columna status
        return isset($this->voided_at) && $this->voided_at !== null;
    }
}
