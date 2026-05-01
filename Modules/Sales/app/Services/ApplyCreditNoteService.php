<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteApplication;
use Modules\Sales\Models\Invoice;

final class ApplyCreditNoteService
{
    /**
     * Apply a portion of a credit note's balance to a target invoice.
     */
    public function apply(CreditNote $creditNote, Invoice $targetInvoice, string $amount, ?int $appliedBy = null): CreditNoteApplication
    {
        throw new InvalidArgumentException(
            __('Direct credit note application is disabled. Register and allocate credit notes using Collections with method :method.', [
                'method' => 'credit_note',
            ])
        );
    }

    /**
     * Record a cash refund against a credit note (reduces its remaining balance).
     */
    public function recordRefund(CreditNote $creditNote, string $refundAmount, ?int $recordedBy = null): void
    {
        if ($creditNote->status !== CreditNoteStatusEnum::Issued) {
            throw new InvalidArgumentException(__('Only issued credit notes can be refunded.'));
        }

        // Re-fetch with a lock to prevent concurrent over-refund.
        $freshCreditNote = DB::transaction(function () use ($creditNote, $refundAmount, $recordedBy): CreditNote {
            $fresh = CreditNote::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($creditNote->getKey());

            $remaining = $fresh->getRemainingBalance();

            if (CollectionAllocationMath::exceedsWithTolerance($refundAmount, $remaining)) {
                throw new InvalidArgumentException(__('The refund amount exceeds the credit note remaining balance.'));
            }

            $newRefunded = bcadd(
                CollectionAllocationMath::normalize($fresh->refunded_amount),
                CollectionAllocationMath::normalize($refundAmount),
                CollectionAllocationMath::SCALE
            );

            $fresh->refunded_amount = $newRefunded;
            $fresh->updated_by = $recordedBy;

            if ($fresh->isFullyConsumed()) {
                $fresh->status = CreditNoteStatusEnum::FullyApplied;
            }

            $fresh->save();

            return $fresh;
        });

        // Sync the caller's instance.
        $creditNote->setRawAttributes($freshCreditNote->getAttributes());
    }
}
