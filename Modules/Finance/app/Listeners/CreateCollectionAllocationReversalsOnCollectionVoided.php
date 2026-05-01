<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Events\CollectionVoided;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Models\CollectionAllocationReversal;

final class CreateCollectionAllocationReversalsOnCollectionVoided implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(CollectionVoided $event): void
    {
        $collection = $event->collection;

        if (! $collection->isVoided()) {
            return;
        }

        $collection->loadMissing('allocations');

        foreach ($collection->allocations as $allocation) {
            $this->createReversalForAllocation($allocation, $collection);
        }
    }

    private function createReversalForAllocation(CollectionAllocation $allocation, mixed $collection): void
    {
        DB::transaction(function () use ($allocation, $collection): void {
            $lockedAllocation = CollectionAllocation::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($allocation->getKey());

            $existing = CollectionAllocationReversal::query()
                ->where('collection_allocation_id', $lockedAllocation->getKey())
                ->where('reversal_type', CollectionAllocationReversal::TYPE_FULL)
                ->first();

            if ($existing !== null) {
                return;
            }

            CollectionAllocationReversal::query()->create([
                'collection_allocation_id' => $lockedAllocation->getKey(),
                'reversal_type' => CollectionAllocationReversal::TYPE_FULL,
                'collection_id' => $collection->getKey(),
                'invoice_id' => $lockedAllocation->invoice_id,
                'reversed_amount' => $lockedAllocation->amount,
                'reason' => (string) ($collection->voided_reason ?: 'Collection voided'),
                'reversed_at' => $collection->voided_at ?? now(),
                'reversed_by' => $collection->updated_by,
                'metadata' => [
                    'allocation_amount_before' => $lockedAllocation->amount,
                    'allocation_allocated_at' => (string) $lockedAllocation->allocated_at,
                    'source' => 'collection_voided_event',
                ],
            ]);
        });
    }
}
