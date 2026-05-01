<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga el XML del documento electrónico SRI (RIDE autorizado o XML firmado)
 * mediante URL firmada temporalmente. No requiere autenticación de sesión.
 */
final class SriXmlController extends Controller
{
    /** @var array<string, class-string> */
    private const TYPE_MAP = [
        'invoice' => Invoice::class,
        'credit-note' => CreditNote::class,
        'debit-note' => DebitNote::class,
    ];

    public function download(Request $request, string $type, int $id): StreamedResponse
    {
        $modelClass = self::TYPE_MAP[$type] ?? null;
        abort_if($modelClass === null, 404);

        /** @var Invoice|CreditNote|DebitNote $document */
        $document = $modelClass::withoutGlobalScopes()->findOrFail($id);

        $metadata = $document->metadata ?? [];
        $xmlDisk = config('sri.electronic.xml_storage_disk', 'local');
        $xmlPath = $metadata['ride_path'] ?? $metadata['xml_path'] ?? null;

        abort_if($xmlPath === null || ! Storage::disk($xmlDisk)->exists($xmlPath), 404);

        $accessKey = method_exists($document, 'getAccessKey')
            ? ($document->getAccessKey() ?? 'documento')
            : 'documento';

        $filename = "{$accessKey}.xml";

        return response()->streamDownload(
            static function () use ($xmlDisk, $xmlPath): void {
                echo Storage::disk($xmlDisk)->get($xmlPath);
            },
            $filename,
            ['Content-Type' => 'application/xml']
        );
    }
}
