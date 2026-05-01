<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Sri\Contracts\GeneratesRidePdf;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Genera el PDF de la Representación Impresa del Documento Electrónico (RIDE)
 * en formato v2, para cualquier modelo que implemente GeneratesRidePdf.
 *
 * Almacena el resultado en metadata['ride_pdf_path'] / ['ride_pdf_disk'].
 * No toca los campos pdf_path / pdf_disk del sistema v1.
 */
final class RidePdfGeneratorService
{
    /**
     * Genera el RIDE PDF y actualiza la metadata del documento.
     */
    public function generate(Model&GeneratesRidePdf $document, string $tenantRuc): string
    {
        $eagerLoads = $document->getRidePdfEagerLoads();

        if ($eagerLoads !== []) {
            $document->loadMissing($eagerLoads);
        }

        $path = $document->getRidePdfStoragePath($tenantRuc);
        $disk = $document->getRidePdfStorageDisk();

        Storage::disk($disk)->makeDirectory(dirname($path));

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $absolutePath = Storage::disk($disk)->path($path);

        Pdf::view($document->getRidePdfView(), array_merge(
            ['document' => $document],
            $document->getRidePdfViewData()
        ))
            ->format('A4')
            ->margins(10, 8, 10, 8)
            ->save($absolutePath);

        $document->update([
            'metadata' => array_merge($document->metadata ?? [], [
                'ride_pdf_path' => $path,
                'ride_pdf_disk' => $disk,
                'ride_pdf_generated_at' => now()->toIso8601String(),
            ]),
        ]);

        return $path;
    }
}
