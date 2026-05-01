<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Services\DocumentEmailSender;
use Modules\People\Models\User;
use Throwable;

final class SendDocumentEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 90;

    /**
     * @param  array{from_email: string, from_name: string|null, to: array<int, string>, cc: array<int, string>, bcc: array<int, string>, subject: string, body: string}  $payload
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly int $userId,
        public readonly string $tenantId,
        public readonly array $payload,
    ) {}

    public function handle(DocumentEmailSender $sender): void
    {
        /** @var (Model&GeneratesPdf)|null $document */
        $document = $this->modelClass::withoutGlobalScopes()->find($this->modelId);
        $user = User::withoutGlobalScopes()->find($this->userId);

        if (! $document || ! $user) {
            return;
        }

        $sender->send($document, $this->payload, $this->tenantId);

        Notification::make()
            ->title(__('Document :code sent by email', ['code' => $document->code]))
            ->success()
            ->sendToDatabase($user);
    }

    public function failed(Throwable $exception): void
    {
        $user = User::withoutGlobalScopes()->find($this->userId);

        Log::error('Error sending document email for '.$this->modelClass.' ID '.$this->modelId.': '.$exception->getMessage(), [
            'exception' => $exception,
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'user_id' => $this->userId,
        ]);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title(__('Document email sending failed'))
            ->danger()
            ->sendToDatabase($user);
    }
}
