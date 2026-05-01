<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Mail\ElectronicDocumentNotificationMailable;
use Modules\Sri\Contracts\GeneratesRidePdf;

/**
 * Orquesta el envío de notificaciones de documentos electrónicos SRI (v2).
 *
 * Garantiza que el RIDE PDF esté generado antes de encolar el mailable.
 * Las URLs firmadas son pre-computadas por el caller (Job) para mantener
 * este servicio libre de dependencias de routing específicas.
 */
final class ElectronicDocumentNotificationService
{
    public function __construct(
        private readonly RidePdfGeneratorService $ridePdfGenerator,
    ) {}

    /**
     * @param  list<string>  $toRecipients
     * @param  list<string>  $ccRecipients
     */
    public function notify(
        Model&GeneratesRidePdf $document,
        string $tenantRuc,
        string $viewUrl,
        string $xmlUrl,
        string $fromEmail,
        string $fromName,
        array $toRecipients,
        array $ccRecipients = [],
    ): void {
        if (empty(($document->metadata ?? [])['ride_pdf_path'])) {
            $this->ridePdfGenerator->generate($document, $tenantRuc);
            $document->refresh();
        }

        $mailable = new ElectronicDocumentNotificationMailable(
            document: $document,
            viewUrl: $viewUrl,
            xmlUrl: $xmlUrl,
            fromEmail: $fromEmail,
            fromName: $fromName,
        );

        $mailer = Mail::to($toRecipients);

        if ($ccRecipients !== []) {
            $mailer->cc($ccRecipients);
        }

        $mailer->queue($mailable);
    }
}
