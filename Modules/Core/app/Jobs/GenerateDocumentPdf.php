<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Services\DocumentPdfGenerator;
use Modules\Core\Services\PdfReadyNotificationFactory;
use Modules\Core\Services\PdfShareLinkService;
use Modules\People\Models\User;
use Throwable;

final class GenerateDocumentPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 60;

    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly int $userId,
        public readonly string $tenantId,
        public readonly bool $notifyWhenReady = true,
    ) {}

    public function handle(
        DocumentPdfGenerator $generator,
        PdfShareLinkService $pdfShareLinkService,
        PdfReadyNotificationFactory $pdfReadyNotificationFactory,
    ): void {
        /** @var (Model&GeneratesPdf)|null $document */
        $document = $this->modelClass::withoutGlobalScopes()->find($this->modelId);
        $user = User::withoutGlobalScopes()->find($this->userId);

        if (! $document || ! $user) {
            return;
        }

        $generator->generate($document, $this->tenantId);

        if (! $this->notifyWhenReady) {
            return;
        }

        $shareLink = $pdfShareLinkService->create($document, (int) $user->getKey());
        $pdfReadyNotificationFactory
            ->make($this->modelClass, $this->modelId, $document, $shareLink)
            ->sendToDatabase($user);
    }

    public function failed(Throwable $exception): void
    {
        $user = User::withoutGlobalScopes()->find($this->userId);

        if (! $user) {
            return;
        }

        Log::error('Error generating PDF for '.$this->modelClass.' ID '.$this->modelId.': '.$exception->getMessage(), [
            'exception' => $exception,
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'user_id' => $this->userId,
        ]);

        Notification::make()
            ->title(__('PDF generation failed'))
            ->danger()
            ->sendToDatabase($user);
    }
}
