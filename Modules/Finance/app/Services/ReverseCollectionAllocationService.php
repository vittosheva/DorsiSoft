<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Models\CollectionAllocationReversal;

final class ReverseCollectionAllocationService
{
    public function reverse(CollectionAllocation $allocation, string $reversedAmount, string $reason, ?int $reversedBy = null): void
    {
        if (bccomp($reversedAmount, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException(__('The reversal amount must be greater than zero.'));
        }

        $currentAmount = (string) $allocation->amount;

        if (bccomp($reversedAmount, $currentAmount, 4) === 1) {
            throw new InvalidArgumentException(__('The reversal amount cannot exceed allocation amount.'));
        }

        if ($allocation->collection?->isVoided()) {
            throw new InvalidArgumentException(__('A voided collection allocation cannot be reversed.'));
        }

        DB::transaction(function () use ($allocation, $reversedAmount, $reason, $reversedBy, $currentAmount): void {
            $isFullReversal = bccomp($reversedAmount, $currentAmount, 4) === 0;
            $remainingAmount = bcsub($currentAmount, $reversedAmount, 4);

            CollectionAllocationReversal::query()->create([
                'collection_id' => $allocation->collection_id,
                'collection_allocation_id' => $allocation->getKey(),
                'invoice_id' => $allocation->invoice_id,
                'reversed_amount' => $reversedAmount,
                'reversal_type' => $isFullReversal
                    ? CollectionAllocationReversal::TYPE_FULL
                    : CollectionAllocationReversal::TYPE_PARTIAL,
                'reason' => $reason,
                'reversed_at' => now(),
                'reversed_by' => $reversedBy,
                'metadata' => [
                    'allocation_amount_before' => $currentAmount,
                    'allocation_amount_after' => $isFullReversal ? '0.0000' : $remainingAmount,
                    'source' => 'manual_collection_allocation_reversal',
                ],
            ]);

            if ($isFullReversal) {
                $allocation->delete();

                return;
            }

            $allocation->amount = $remainingAmount;
            $allocation->save();
        });
    }
}
