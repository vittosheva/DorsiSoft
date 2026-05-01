<?php

declare(strict_types=1);

namespace Modules\Sri\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Sri\Concerns\HasElectronicEvents;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Sri\Exceptions\XmlSigningException;
use Modules\Sri\Exceptions\XsdValidationException;
use Modules\Sri\Services\ElectronicDocumentOrchestrator;
use Modules\Sri\Services\ElectronicEventLogger;
use Throwable;

final class ProcessElectronicDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        private readonly string $modelClass,
        private readonly int|string $modelId,
        private readonly ?int $triggeredBy = null,
    ) {}

    public function handle(ElectronicDocumentOrchestrator $orchestrator): void
    {
        /** @var (Model&HasElectronicBilling)|null $document */
        $document = $this->modelClass::withoutGlobalScopes()->find($this->modelId);

        if (! $document) {
            Log::warning("ProcessElectronicDocument: document not found [{$this->modelClass}:{$this->modelId}]");

            return;
        }

        if ($document->getElectronicStatus()?->isTerminal()) {
            return;
        }

        try {
            $orchestrator->process($document, $this->triggeredBy);
        } catch (XmlGenerationException|XsdValidationException|XmlSigningException $e) {
            // Deterministic configuration/data errors: fail immediately without retrying
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessElectronicDocument failed', [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        /** @var (Model&HasElectronicBilling)|null $document */
        $document = $this->modelClass::withoutGlobalScopes()->find($this->modelId);

        if ($document) {
            $statusFrom = $document->getElectronicStatus();

            $document->update([
                'electronic_status' => ElectronicStatusEnum::Error,
                'metadata' => array_merge($document->metadata ?? [], [
                    'error' => $exception->getMessage(),
                ]),
            ]);

            if ($document instanceof HasElectronicEvents) {
                ElectronicEventLogger::record(
                    document: $document,
                    event: 'failed',
                    statusFrom: $statusFrom,
                    statusTo: ElectronicStatusEnum::Error,
                    payload: ['error' => $exception->getMessage()],
                    triggeredBy: $this->triggeredBy,
                );
            }
        }
    }
}
