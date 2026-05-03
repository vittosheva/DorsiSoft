<?php

declare(strict_types=1);

namespace Modules\Sales\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Modules\Sri\Contracts\GeneratesRidePdf;
use Modules\Sri\Services\ElectronicDocumentNotificationService;
use RuntimeException;

/**
 * Encola el envío de la notificación de documento electrónico SRI (v2).
 *
 * Genera las URLs firmadas temporales aquí (en el Job) para mantener
 * ElectronicDocumentNotificationService libre de dependencias de routing.
 */
final class SendElectronicDocumentNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 90;

    /**
     * @param  class-string  $modelClass  FQCN del modelo, e.g. Invoice::class
     * @param  list<string>  $toRecipients
     * @param  list<string>  $ccRecipients
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly string $tenantRuc,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly array $toRecipients,
        public readonly array $ccRecipients = [],
    ) {}

    public function handle(ElectronicDocumentNotificationService $service): void
    {
        $document = ($this->modelClass)::withoutGlobalScopes()->findOrFail($this->modelId);

        if (! $document instanceof GeneratesRidePdf) {
            throw new RuntimeException("{$this->modelClass} does not implement GeneratesRidePdf.");
        }

        $viewUrl = URL::temporarySignedRoute(
            'sales.v1.ride.view',
            now()->addDays(7),
            ['type' => $document->getRidePdfType(), 'id' => $this->modelId]
        );

        $xmlUrl = URL::temporarySignedRoute(
            'sales.v1.xml.download',
            now()->addDays(7),
            ['type' => $document->getRidePdfType(), 'id' => $this->modelId]
        );

        $service->notify(
            document: $document,
            tenantRuc: $this->tenantRuc,
            viewUrl: $viewUrl,
            xmlUrl: $xmlUrl,
            fromEmail: $this->fromEmail,
            fromName: $this->fromName,
            toRecipients: $this->toRecipients,
            ccRecipients: $this->ccRecipients,
        );
    }
}
