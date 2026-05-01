<?php

declare(strict_types=1);

namespace Modules\Core\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

/**
 * Mailable genérico para notificaciones de documentos electrónicos SRI (v2).
 *
 * Compatible con cualquier modelo que implemente GeneratesRidePdf + HasElectronicBilling.
 * Adjunta el RIDE PDF y el XML (si existen en metadata).
 */
final class ElectronicDocumentNotificationMailable extends Mailable implements ShouldQueue
{
    public function __construct(
        public readonly Model $document,
        public readonly string $viewUrl,
        public readonly string $xmlUrl,
        public readonly string $fromEmail,
        public readonly string $fromName,
    ) {}

    public function envelope(): Envelope
    {
        $label = method_exists($this->document, 'getRideSriDocumentTypeLabel')
            ? $this->document->getRideSriDocumentTypeLabel()
            : 'Comprobante Electrónico';

        $seq = method_exists($this->document, 'getSriSequentialCode')
            ? ($this->document->getSriSequentialCode() ?? '')
            : ($this->document->code ?? '');

        $company = $this->document->company?->legal_name ?? '';

        $subject = mb_trim(implode(' ', array_filter([$label, $seq, $company ? "- {$company}" : ''])));

        return new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'core::mail.v2.electronic-document-notification',
            with: [
                'document' => $this->document,
                'viewUrl' => $this->viewUrl,
                'xmlUrl' => $this->xmlUrl,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        $metadata = $this->document->metadata ?? [];

        // RIDE PDF attachment
        $ridePdfPath = $metadata['ride_pdf_path'] ?? null;
        $ridePdfDisk = $metadata['ride_pdf_disk'] ?? 'local';

        if ($ridePdfPath && Storage::disk($ridePdfDisk)->exists($ridePdfPath)) {
            $filename = method_exists($this->document, 'getRidePdfDownloadFilename')
                ? $this->document->getRidePdfDownloadFilename()
                : 'documento.pdf';

            $attachments[] = Attachment::fromStorageDisk($ridePdfDisk, $ridePdfPath)
                ->as($filename)
                ->withMime('application/pdf');
        }

        // XML attachment (ride_path = authorized XML from SRI; xml_path = signed XML)
        $xmlDisk = config('sri.electronic.xml_storage_disk', 'local');
        $xmlPath = $metadata['ride_path'] ?? $metadata['xml_path'] ?? null;

        if ($xmlPath && Storage::disk($xmlDisk)->exists($xmlPath)) {
            $accessKey = method_exists($this->document, 'getAccessKey')
                ? ($this->document->getAccessKey() ?? 'documento')
                : 'documento';

            $attachments[] = Attachment::fromStorageDisk($xmlDisk, $xmlPath)
                ->as("{$accessKey}.xml")
                ->withMime('application/xml');
        }

        return $attachments;
    }
}
