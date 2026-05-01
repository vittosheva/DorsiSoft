<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class AllocateCollectionToInvoiceService
{
    public function allocate(Collection $collection, int $invoiceId, string $amount): CollectionAllocation
    {
        if (bccomp(CollectionAllocationMath::normalize($amount), '0.0000', CollectionAllocationMath::SCALE) <= 0) {
            throw new InvalidArgumentException(__('The allocated amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($collection, $invoiceId, $amount): CollectionAllocation {
            $freshCollection = Collection::query()
                ->lockForUpdate()
                ->findOrFail($collection->getKey());

            if ($freshCollection->isVoided()) {
                throw new InvalidArgumentException(__('Cannot allocate a voided collection.'));
            }

            $freshInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId, ['id', 'business_partner_id', 'customer_name', 'total']);

            $requestedAmount = CollectionAllocationMath::normalize($amount);

            $remainingCollectionAmount = $this->resolveRemainingCollectionAmount($freshCollection->getKey(), $freshCollection->amount);

            if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $remainingCollectionAmount)) {
                throw new InvalidArgumentException(__('The allocated amount cannot exceed the remaining collection balance.'));
            }

            $pendingInvoiceAmount = $this->resolvePendingInvoiceAmount($freshInvoice->getKey(), $freshInvoice->total);

            if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $pendingInvoiceAmount)) {
                throw new InvalidArgumentException(__('The allocated amount cannot exceed the pending invoice balance.'));
            }

            [$creditNoteId, $originInvoiceId] = $this->resolveCreditNoteTraceability($freshCollection, $requestedAmount);

            $allocation = CollectionAllocation::query()->create([
                'company_id' => $freshCollection->company_id,
                'collection_id' => $freshCollection->getKey(),
                'credit_note_id' => $creditNoteId,
                'origin_invoice_id' => $originInvoiceId,
                'invoice_id' => $freshInvoice->getKey(),
                'amount' => $requestedAmount,
                'allocated_at' => now(),
            ]);

            // When the NC credit is applied to its own origin invoice, the legacy
            // credited_amount on that invoice (set by old observers) is now redundant.
            // Zero it out so reports don't double-count (paid_amount now covers it).
            if ($creditNoteId !== null && $originInvoiceId !== null && $originInvoiceId === $freshInvoice->getKey()) {
                Invoice::withoutGlobalScopes()
                    ->where('id', $freshInvoice->getKey())
                    ->update(['credited_amount' => '0.0000']);
            }

            return $allocation;
        }, 3);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveCreditNoteTraceability(Collection $collection, string $requestedAmount): array
    {
        if ($collection->collection_method !== CollectionMethodEnum::CreditNote) {
            return [null, null];
        }

        if (! $collection->credit_note_id) {
            throw new InvalidArgumentException(__('Credit note collections must reference a credit note.'));
        }

        $creditNote = CreditNote::withoutGlobalScopes()
            ->lockForUpdate()
            ->findOrFail($collection->credit_note_id, [
                'id',
                'invoice_id',
                'total',
                'refunded_amount',
                'status',
                'electronic_status',
            ]);

        if ($creditNote->status === CreditNoteStatusEnum::Draft || $creditNote->status === CreditNoteStatusEnum::Voided) {
            throw new InvalidArgumentException(__('Only active credit notes can be allocated.'));
        }

        if ($creditNote->electronic_status !== ElectronicStatusEnum::Authorized) {
            throw new InvalidArgumentException(__('Only authorized credit notes can be allocated.'));
        }

        $alreadyApplied = CollectionAllocation::query()
            ->where('credit_note_id', $creditNote->getKey())
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        $availableCredit = CollectionAllocationMath::pending(
            CollectionAllocationMath::pending($creditNote->total, CollectionAllocationMath::normalize($creditNote->refunded_amount)),
            CollectionAllocationMath::normalize($alreadyApplied),
        );

        if (CollectionAllocationMath::exceedsWithTolerance($requestedAmount, $availableCredit)) {
            throw new InvalidArgumentException(__('The allocated amount cannot exceed the available credit note balance.'));
        }

        return [$creditNote->getKey(), $creditNote->invoice_id];
    }

    private function resolveRemainingCollectionAmount(int $collectionId, mixed $collectionAmount): string
    {
        $allocatedAmount = CollectionAllocation::query()
            ->where('collection_id', $collectionId)
            ->sum('amount');

        return CollectionAllocationMath::pending($collectionAmount, CollectionAllocationMath::normalize($allocatedAmount));
    }

    private function resolvePendingInvoiceAmount(int $invoiceId, mixed $invoiceTotal): string
    {
        $paidFromCollections = CollectionAllocation::query()
            ->where('invoice_id', $invoiceId)
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        return CollectionAllocationMath::pending($invoiceTotal, CollectionAllocationMath::normalize($paidFromCollections));
    }
}
