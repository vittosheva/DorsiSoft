<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Mail\DocumentEmailMailable;

final class DocumentEmailSender
{
    /**
     * @param  array{from_email: string, from_name: string|null, to: array<int, string>, cc: array<int, string>, bcc: array<int, string>, subject: string, body: string}  $payload
     */
    public function send(Model&GeneratesPdf $document, array $payload, string $tenantId): void
    {
        [$disk, $path] = $this->ensurePdfIsAvailable($document, $tenantId);

        $message = Mail::to($payload['to']);

        if ($payload['cc'] !== []) {
            $message->cc($payload['cc']);
        }

        if ($payload['bcc'] !== []) {
            $message->bcc($payload['bcc']);
        }

        $message->send(new DocumentEmailMailable(
            subjectLine: $payload['subject'],
            body: $payload['body'],
            fromEmail: $payload['from_email'],
            fromName: $payload['from_name'],
            documentCode: (string) $document->code,
            attachmentDisk: $disk,
            attachmentPath: $path,
            attachmentName: $document->getPdfDownloadFilename(),
        ));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function ensurePdfIsAvailable(Model&GeneratesPdf $document, string $tenantId): array
    {
        $disk = (string) ($document->metadata['pdf_disk'] ?? 'local');
        $path = (string) ($document->metadata['pdf_path'] ?? '');

        if ($path !== '' && Storage::disk($disk)->exists($path)) {
            return [$disk, $path];
        }

        app(DocumentPdfGenerator::class)->generate($document, $tenantId);

        $document->refresh();

        $generatedDisk = (string) ($document->metadata['pdf_disk'] ?? 'local');
        $generatedPath = (string) ($document->metadata['pdf_path'] ?? '');

        return [$generatedDisk, $generatedPath];
    }
}
