<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Models\PdfShareLink;
use Modules\Core\Support\Pdf\PdfDocumentRouteKey;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PdfDownloadController extends Controller
{
    public function view(Request $request, string $model, int $id): BinaryFileResponse
    {
        $document = $this->resolvePdfDocument($model, $id);

        abort_if($document === null, 404);

        abort_unless(
            (int) $request->user()?->company_id === (int) $document->company_id,
            403,
        );

        return $this->inlineDocument($document);
    }

    public function download(Request $request, string $model, int $id): BinaryFileResponse
    {
        $document = $this->resolvePdfDocument($model, $id);

        abort_if($document === null, 404);

        abort_unless(
            (int) $request->user()?->company_id === (int) $document->company_id,
            403,
        );

        return $this->downloadDocument($document);
    }

    public function viewShared(PdfShareLink $pdfShareLink): BinaryFileResponse
    {
        abort_unless($pdfShareLink->isActive(), 404);

        $document = $this->resolvePdfDocument($pdfShareLink->shareable_type, (int) $pdfShareLink->shareable_id);

        abort_if($document === null, 404);

        $this->touchShareLink($pdfShareLink);

        return $this->inlineDocument($document);
    }

    public function downloadShared(PdfShareLink $pdfShareLink): BinaryFileResponse
    {
        abort_unless($pdfShareLink->isActive(), 404);

        $document = $this->resolvePdfDocument($pdfShareLink->shareable_type, (int) $pdfShareLink->shareable_id);

        abort_if($document === null, 404);

        $this->touchShareLink($pdfShareLink);

        return $this->downloadDocument($document);
    }

    /**
     * @return (Model&GeneratesPdf)|null
     */
    private function resolvePdfDocument(string $modelClass, int $id): ?Model
    {
        $resolvedModelClass = PdfDocumentRouteKey::resolve(urldecode($modelClass));

        if ($resolvedModelClass === null) {
            return null;
        }

        /** @var (Model&GeneratesPdf)|null $document */
        $document = $resolvedModelClass::withoutGlobalScopes()->find($id);

        return $document;
    }

    private function downloadDocument(Model&GeneratesPdf $document): BinaryFileResponse
    {
        [$absolutePath, $filename] = $this->resolveStoredPdf($document);

        return response()->download($absolutePath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function inlineDocument(Model&GeneratesPdf $document): BinaryFileResponse
    {
        [$absolutePath, $filename] = $this->resolveStoredPdf($document);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveStoredPdf(Model&GeneratesPdf $document): array
    {
        $relativePath = $document->metadata['pdf_path'] ?? null;
        $disk = $document->metadata['pdf_disk'] ?? 'local';

        abort_if($relativePath === null || ! Storage::disk($disk)->exists($relativePath), 404);

        $filename = $document->getPdfDownloadFilename();
        $absolutePath = Storage::disk($disk)->path($relativePath);

        return [$absolutePath, $filename];
    }

    private function touchShareLink(PdfShareLink $pdfShareLink): void
    {
        $pdfShareLink->forceFill([
            'last_accessed_at' => now(),
        ])->save();
    }
}
