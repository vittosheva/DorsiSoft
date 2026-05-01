<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteApplication;
use Modules\Sales\Models\Invoice;

final class CreditNoteApplicationObserver
{
    public function created(CreditNoteApplication $application): void
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($application->invoice_id);
        $creditNote = CreditNote::withoutGlobalScopes()->findOrFail($application->credit_note_id);

        $this->syncTargetInvoicePaidAmount($invoice);
        $this->syncCreditNoteAppliedAmount($creditNote);
    }

    public function deleted(CreditNoteApplication $application): void
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($application->invoice_id);
        $creditNote = CreditNote::withoutGlobalScopes()->findOrFail($application->credit_note_id);

        $this->syncTargetInvoicePaidAmount($invoice);
        $this->syncCreditNoteAppliedAmount($creditNote);
    }

    public function updated(CreditNoteApplication $application): void
    {
        if (! $application->isDirty('amount')) {
            return;
        }

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($application->invoice_id);
        $creditNote = CreditNote::withoutGlobalScopes()->findOrFail($application->credit_note_id);

        $this->syncTargetInvoicePaidAmount($invoice);
        $this->syncCreditNoteAppliedAmount($creditNote);
    }

    /**
     * Sync the TARGET invoice's paid_amount.
     * Includes both collection allocations and credit note applications received.
     */
    private function syncTargetInvoicePaidAmount(Invoice $invoice): void
    {
        $fromCollections = CollectionAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('collection', fn ($q) => $q->whereNull('voided_at'))
            ->sum('amount');

        $fromCreditApps = CreditNoteApplication::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('creditNote', fn ($q) => $q->whereNotIn('status', [CreditNoteStatusEnum::Voided->value]))
            ->sum('amount');

        $totalPaid = bcadd(
            CollectionAllocationMath::normalize($fromCollections),
            CollectionAllocationMath::normalize($fromCreditApps),
            CollectionAllocationMath::SCALE
        );

        $invoice->paid_amount = $totalPaid;

        $settled = bcadd(
            CollectionAllocationMath::normalize($totalPaid),
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

    /**
     * Sync the credit note's applied_amount and transition to FullyApplied when exhausted.
     */
    private function syncCreditNoteAppliedAmount(CreditNote $creditNote): void
    {
        $appliedAmount = CreditNoteApplication::query()
            ->where('credit_note_id', $creditNote->getKey())
            ->sum('amount');

        $creditNote->applied_amount = CollectionAllocationMath::normalize($appliedAmount);

        if ($creditNote->isFullyConsumed() && $creditNote->status === CreditNoteStatusEnum::Issued) {
            $creditNote->status = CreditNoteStatusEnum::FullyApplied;
        } elseif (! $creditNote->isFullyConsumed() && $creditNote->status === CreditNoteStatusEnum::FullyApplied) {
            $creditNote->status = CreditNoteStatusEnum::Issued;
        }

        $creditNote->saveQuietly();
    }
}
