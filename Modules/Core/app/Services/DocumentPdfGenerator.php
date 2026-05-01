<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\GeneratesPdf;
use Spatie\LaravelPdf\Facades\Pdf;

final class DocumentPdfGenerator
{
    /**
     * Generate and persist a PDF for the given document.
     *
     * @return string The relative storage path of the generated file
     */
    public function generate(Model&GeneratesPdf $document, string $tenantId): string
    {
        $fileType = $document->getPdfFileType();
        $disk = FileStoragePathService::getDisk($fileType);
        $directory = FileStoragePathService::getPath($fileType, $document, $tenantId);
        $filename = str($document->code)->slug()->append('.pdf')->toString();
        $relativePath = $directory.'/'.$filename;
        $absolutePath = Storage::disk($disk)->path($relativePath);

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }

        Storage::disk($disk)->makeDirectory($directory);

        $document->loadMissing($document->getPdfEagerLoads());

        Pdf::view($document->getPdfView(), array_merge(
            ['document' => $document],
            $document->getPdfViewData(),
        ))
            ->footerView('core::pdf.partials.footer', ['document' => $document])
            ->margins(10, 10, 18, 10)
            ->format('A4')
            ->save($absolutePath);

        $document->update([
            'metadata' => array_merge((array) ($document->metadata ?? []), [
                'pdf_path' => $relativePath,
                'pdf_disk' => $disk,
                'pdf_generated_at' => now()->toIso8601String(),
            ]),
        ]);

        return $relativePath;
    }
}
