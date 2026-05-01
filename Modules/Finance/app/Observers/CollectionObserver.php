<?php

declare(strict_types=1);

namespace Modules\Finance\Observers;

use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;

final class CollectionObserver
{
    public function updating(Collection $collection): void
    {
        if ($collection->isDirty('voided_at') && $collection->voided_at !== null && $collection->getOriginal('voided_at') === null) {
            $affectedInvoiceIds = $collection->allocations()->pluck('invoice_id');

            foreach ($affectedInvoiceIds as $invoiceId) {
                $this->syncInvoicePaidAmountExcluding(
                    Invoice::withoutGlobalScopes()->findOrFail($invoiceId),
                    $collection->getKey()
                );
            }
        }
    }

    private function syncInvoicePaidAmountExcluding(Invoice $invoice, int $excludedCollectionId): void
    {
        $paidAmount = CollectionAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->where('collection_id', '!=', $excludedCollectionId)
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        $invoice->paid_amount = CollectionAllocationMath::normalize($paidAmount);

        $settled = bcadd(
            CollectionAllocationMath::normalize($paidAmount),
            CollectionAllocationMath::normalize($invoice->credited_amount),
            CollectionAllocationMath::SCALE
        );

        if (CollectionAllocationMath::isEffectivelyZero($settled) && $invoice->status === InvoiceStatusEnum::Paid) {
            $invoice->status = InvoiceStatusEnum::Issued;
        } elseif (CollectionAllocationMath::isPaid($settled, $invoice->total) && $invoice->status === InvoiceStatusEnum::Issued) {
            $invoice->status = InvoiceStatusEnum::Paid;
        }

        $invoice->saveQuietly();
    }
}
