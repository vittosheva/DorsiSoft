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
use Modules\Core\Models\Company;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Models\Withholding;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Services\ElectronicDocumentOrchestrator;
use Throwable;

/**
 * Polls SRI for authorization of documents that are in 'submitted' state
 * for more than 2 minutes. Scheduled every 5 minutes by SriServiceProvider.
 *
 * Processes up to 50 pending documents per run to avoid queue saturation.
 */
final class PollSriAuthorization implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /** @var list<class-string<Model&HasElectronicBilling>> */
    private array $documentClasses = [
        Invoice::class,
        CreditNote::class,
        DebitNote::class,
        Withholding::class,
        DeliveryGuide::class,
        PurchaseSettlement::class,
    ];

    public function handle(ElectronicDocumentOrchestrator $orchestrator): void
    {
        $polledCount = 0;
        $maxPerRun = 50;

        foreach ($this->documentClasses as $modelClass) {
            if ($polledCount >= $maxPerRun) {
                break;
            }

            $remaining = $maxPerRun - $polledCount;

            $documents = $modelClass::withoutGlobalScopes()
                ->where('electronic_status', ElectronicStatusEnum::Submitted->value)
                ->where('electronic_submitted_at', '<=', now()->subMinutes(2))
                ->limit($remaining)
                ->get();

            foreach ($documents as $document) {
                try {
                    $company = Company::withoutGlobalScopes()->find($document->company_id);
                    $orchestrator->pollAuthorization($document, $company);
                    $polledCount++;
                } catch (Throwable $e) {
                    Log::error('PollSriAuthorization failed for document', [
                        'model_class' => $modelClass,
                        'document_id' => $document->id,
                        'access_key' => $document->access_key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($polledCount > 0) {
            Log::info("PollSriAuthorization: polled {$polledCount} documents.");
        }
    }
}
