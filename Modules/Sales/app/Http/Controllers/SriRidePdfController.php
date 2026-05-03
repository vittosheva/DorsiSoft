<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve el RIDE PDF v2 de forma inline mediante URL firmada temporalmente.
 * No requiere autenticación de sesión — la firma garantiza el acceso.
 */
final class SriRidePdfController extends Controller
{
    /** @var array<string, class-string> */
    private const TYPE_MAP = [
        'invoice' => Invoice::class,
        'credit-note' => CreditNote::class,
        'debit-note' => DebitNote::class,
    ];

    public function view(Request $request, string $type, int $id): StreamedResponse
    {
        $modelClass = self::TYPE_MAP[$type] ?? null;
        abort_if($modelClass === null, 404);

        /** @var Model $document */
        $document = $modelClass::query()->withoutGlobalScopes()->findOrFail($id);

        $metadata = $document->metadata ?? [];
        $ridePdfPath = $metadata['pdf_path'] ?? null; // ride_pdf_path
        $ridePdfDisk = $metadata['ride_pdf_disk'] ?? 'local';

        abort_if($ridePdfPath === null || ! Storage::disk($ridePdfDisk)->exists($ridePdfPath), 404);

        $filename = $document->getRidePdfDownloadFilename();

        return response()->streamDownload(
            static function () use ($ridePdfDisk, $ridePdfPath): void {
                echo Storage::disk($ridePdfDisk)->get($ridePdfPath);
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]
        );
    }
}
