<?php

declare(strict_types=1);

namespace Modules\System\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Establishment;
use Modules\System\Exceptions\DocumentSeriesNotFoundException;
use Modules\System\Models\DocumentSeries;
use Modules\System\Models\DocumentType;

final class DocumentNumberingService
{
    /**
     * Genera el siguiente número para el tipo de documento dado.
     * Usa DB::transaction + lockForUpdate para garantizar unicidad en alto volumen.
     *
     * @throws DocumentSeriesNotFoundException
     */
    public function generate(DocumentType $documentType, ?Establishment $establishment = null): string
    {
        return DB::transaction(function () use ($documentType, $establishment): string {
            $series = DocumentSeries::query()
                ->where('document_type_id', $documentType->id)
                ->where('is_active', true)
                ->when(
                    $establishment !== null,
                    fn ($q) => $q->where('establishment_id', $establishment->id),
                    fn ($q) => $q->whereNull('establishment_id'),
                )
                ->lockForUpdate()
                ->first();

            if ($series === null) {
                throw new DocumentSeriesNotFoundException(
                    __('No active series found for document type [:type].', ['type' => $documentType->name])
                );
            }

            return $series->increment();
        });
    }

    /**
     * Verifica si existe una serie activa para el tipo de documento.
     */
    public function hasSeries(DocumentType $documentType, ?Establishment $establishment = null): bool
    {
        return DocumentSeries::query()
            ->where('document_type_id', $documentType->id)
            ->where('is_active', true)
            ->when(
                $establishment !== null,
                fn ($q) => $q->where('establishment_id', $establishment->id),
                fn ($q) => $q->whereNull('establishment_id'),
            )
            ->exists();
    }
}
