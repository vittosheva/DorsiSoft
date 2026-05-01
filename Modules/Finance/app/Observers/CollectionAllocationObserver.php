<?php

declare(strict_types=1);

namespace Modules\Finance\Observers;

use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;

final class CollectionAllocationObserver
{
    public function created(CollectionAllocation $allocation): void
    {
        $this->syncInvoicePaidAmount(
            Invoice::withoutGlobalScopes()->findOrFail($allocation->invoice_id)
        );
    }

    public function deleted(CollectionAllocation $allocation): void
    {
        $this->syncInvoicePaidAmount(
            Invoice::withoutGlobalScopes()->findOrFail($allocation->invoice_id)
        );
    }

    public function updated(CollectionAllocation $allocation): void
    {
        if (! $allocation->isDirty('amount')) {
            return;
        }

        $this->syncInvoicePaidAmount(
            Invoice::withoutGlobalScopes()->findOrFail($allocation->invoice_id)
        );
    }

    private function syncInvoicePaidAmount(Invoice $invoice): void
    {
        $paidAmount = CollectionAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('collection', fn ($query) => $query->whereNull('voided_at'))
            ->sum('amount');

        $invoice->paid_amount = CollectionAllocationMath::normalize($paidAmount);

        $settled = bcadd(
            CollectionAllocationMath::normalize($paidAmount),
            CollectionAllocationMath::normalize($invoice->credited_amount),
            CollectionAllocationMath::SCALE
        );

        if (CollectionAllocationMath::isPaid($settled, $invoice->total) && $invoice->status === InvoiceStatusEnum::Issued) {
            $invoice->status = InvoiceStatusEnum::Paid;
        } elseif (CollectionAllocationMath::isEffectivelyZero($settled) && $invoice->status === InvoiceStatusEnum::Paid) {
            $invoice->status = InvoiceStatusEnum::Issued;
        }

        $invoice->saveQuietly();
    }
}
