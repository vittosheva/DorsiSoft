<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Finance\Services\ReverseCollectionAllocationService;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Events\CreditNoteIssued;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteItem;
use Modules\Sales\Models\Invoice;

final class IssueCreditNoteService
{
    public function __construct(
        private readonly ReverseCollectionAllocationService $reverseService,
    ) {}

    /**
     * Issue a credit note from a collection allocation reversal.
     * Reverses the allocation (freeing collection balance) and creates a formal NC document.
     *
     * @param  array<int, array{product_id: ?int, product_code: ?string, product_name: ?string, product_unit: ?string, quantity: string, unit_price: string, discount_type: ?string, discount_value: ?string, discount_amount: string, subtotal: string, tax_amount: string, total: string, sort_order?: int}>  $items
     */
    public function fromCollectionReversal(
        CollectionAllocation $allocation,
        array $items,
        string $reason,
        string $creditDisposition,
        ?int $issuedBy = null,
    ): CreditNote {
        if (empty($items)) {
            throw new InvalidArgumentException(__('At least one item is required to issue a credit note.'));
        }

        if (! blank($allocation->collection?->metadata['credit_note_id'] ?? null)) {
            throw new InvalidArgumentException(__('This allocation is already linked to a credit note.'));
        }

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($allocation->invoice_id);

        $totalAmount = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['total']), CollectionAllocationMath::SCALE),
            '0.0000'
        );

        if (bccomp($totalAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
            throw new InvalidArgumentException(__('The credit note total must be greater than zero.'));
        }

        return DB::transaction(function () use ($allocation, $items, $reason, $creditDisposition, $issuedBy, $invoice, $totalAmount): CreditNote {
            // Reverse the collection allocation first.
            $this->reverseService->reverse($allocation, $totalAmount, $reason, $issuedBy);

            // Retrieve the reversal that was just created
            $reversal = $allocation->reversals()->latest()->first();

            $creditNote = $this->createCreditNote($invoice, $items, $reason, $totalAmount, $issuedBy, [
                'collection_id' => $allocation->collection_id,
                'collection_allocation_reversal_id' => $reversal?->getKey(),
            ]);

            if ($creditDisposition === 'cash_refunded') {
                $creditNote->refunded_amount = $totalAmount;
                $creditNote->saveQuietly();
            }

            // Link reversal metadata back to the NC for traceability
            if ($reversal) {
                $reversal->metadata = array_merge($reversal->metadata ?? [], ['credit_note_id' => $creditNote->getKey()]);
                $reversal->saveQuietly();
            }

            CreditNoteIssued::dispatch($creditNote);

            return $creditNote;
        });
    }

    /**
     * Issue a standalone credit note directly from an invoice (no collection reversal).
     *
     * @param  array<int, array{product_id: ?int, product_code: ?string, product_name: ?string, product_unit: ?string, quantity: string, unit_price: string, discount_type: ?string, discount_value: ?string, discount_amount: string, subtotal: string, tax_amount: string, total: string, sort_order?: int}>  $items
     */
    public function fromInvoice(
        Invoice $invoice,
        array $items,
        string $reason,
        ?int $issuedBy = null,
    ): CreditNote {
        if (empty($items)) {
            throw new InvalidArgumentException(__('At least one item is required to issue a credit note.'));
        }

        $totalAmount = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['total']), CollectionAllocationMath::SCALE),
            '0.0000'
        );

        if (bccomp($totalAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
            throw new InvalidArgumentException(__('The credit note total must be greater than zero.'));
        }

        if (CollectionAllocationMath::exceedsWithTolerance($totalAmount, (string) $invoice->total)) {
            throw new InvalidArgumentException(__('The credit note total cannot exceed the invoice total.'));
        }

        return DB::transaction(function () use ($invoice, $items, $reason, $totalAmount, $issuedBy): CreditNote {
            $creditNote = $this->createCreditNote($invoice, $items, $reason, $totalAmount, $issuedBy, []);

            CreditNoteIssued::dispatch($creditNote);

            return $creditNote;
        });
    }

    /**
     * Issue a standalone credit note without an invoice reference.
     * Useful for systems where NCs are created manually without linking to an existing invoice.
     *
     * @param  array<string, mixed>  $customerSnapshot  Keys: company_id, business_partner_id, customer_name, customer_trade_name, customer_identification_type, customer_identification, customer_address, customer_email, customer_phone, currency_code
     * @param  array<int, array{product_id: ?int, product_code: ?string, product_name: ?string, product_unit: ?string, quantity: string, unit_price: string, discount_type: ?string, discount_value: ?string, discount_amount: string, subtotal: string, tax_amount: string, total: string, sort_order?: int}>  $items
     */
    public function standalone(
        array $customerSnapshot,
        array $items,
        string $reason,
        ?string $issuedAt = null,
        ?string $invoiceId = null,
        ?int $issuedBy = null,
    ): CreditNote {
        if (empty($items)) {
            throw new InvalidArgumentException(__('At least one item is required to issue a credit note.'));
        }

        $totalAmount = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['total']), CollectionAllocationMath::SCALE),
            '0.0000'
        );

        if (bccomp($totalAmount, '0.0000', CollectionAllocationMath::SCALE) <= 0) {
            throw new InvalidArgumentException(__('The credit note total must be greater than zero.'));
        }

        return DB::transaction(function () use ($customerSnapshot, $items, $reason, $totalAmount, $issuedAt, $invoiceId, $issuedBy): CreditNote {
            $subtotal = array_reduce(
                $items,
                fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['subtotal']), CollectionAllocationMath::SCALE),
                '0.0000'
            );

            $taxAmount = array_reduce(
                $items,
                fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['tax_amount']), CollectionAllocationMath::SCALE),
                '0.0000'
            );

            $creditNote = CreditNote::create(array_merge($customerSnapshot, [
                'invoice_id' => $invoiceId,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $totalAmount,
                'applied_amount' => '0.0000',
                'refunded_amount' => '0.0000',
                'status' => CreditNoteStatusEnum::Issued,
                'reason' => $reason,
                'issue_date' => $issuedAt ?? now()->toDateString(),
                'created_by' => $issuedBy,
                'updated_by' => $issuedBy,
            ]));

            foreach ($items as $index => $item) {
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->getKey(),
                    'product_id' => $item['product_id'] ?? null,
                    'product_code' => $item['product_code'] ?? null,
                    'product_name' => $item['product_name'] ?? null,
                    'product_unit' => $item['product_unit'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index,
                    'description' => $item['description'] ?? null,
                    'quantity' => CollectionAllocationMath::normalize($item['quantity']),
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $item['discount_value'] ?? null,
                    'discount_amount' => CollectionAllocationMath::normalize($item['discount_amount']),
                    'subtotal' => CollectionAllocationMath::normalize($item['subtotal']),
                    'tax_amount' => CollectionAllocationMath::normalize($item['tax_amount']),
                    'total' => CollectionAllocationMath::normalize($item['total']),
                ]);
            }

            CreditNoteIssued::dispatch($creditNote);

            return $creditNote;
        });
    }

    /**
     * @param  array<int, array{product_id: ?int, product_code: ?string, product_name: ?string, product_unit: ?string, quantity: string, unit_price: string, discount_type: ?string, discount_value: ?string, discount_amount: string, subtotal: string, tax_amount: string, total: string, sort_order?: int}>  $items
     * @param  array<string, mixed>  $extra
     */
    private function createCreditNote(Invoice $invoice, array $items, string $reason, string $totalAmount, ?int $issuedBy, array $extra): CreditNote
    {
        $subtotal = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['subtotal']), CollectionAllocationMath::SCALE),
            '0.0000'
        );

        $taxAmount = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, CollectionAllocationMath::normalize($item['tax_amount']), CollectionAllocationMath::SCALE),
            '0.0000'
        );

        $creditNote = CreditNote::create(array_merge([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->getKey(),
            'business_partner_id' => $invoice->business_partner_id,
            'customer_name' => $invoice->customer_name,
            'customer_trade_name' => $invoice->customer_trade_name,
            'customer_identification_type' => $invoice->customer_identification_type,
            'customer_identification' => $invoice->customer_identification,
            'customer_address' => $invoice->customer_address,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'currency_code' => $invoice->currency_code,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $totalAmount,
            'applied_amount' => '0.0000',
            'refunded_amount' => '0.0000',
            'status' => CreditNoteStatusEnum::Issued,
            'reason' => $reason,
            'issue_date' => now()->toDateString(),
            'created_by' => $issuedBy,
            'updated_by' => $issuedBy,
        ], $extra));

        foreach ($items as $index => $item) {
            CreditNoteItem::create([
                'credit_note_id' => $creditNote->getKey(),
                'product_id' => $item['product_id'] ?? null,
                'product_code' => $item['product_code'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'product_unit' => $item['product_unit'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
                'description' => $item['description'] ?? null,
                'quantity' => CollectionAllocationMath::normalize($item['quantity']),
                'unit_price' => $item['unit_price'],
                'discount_type' => $item['discount_type'] ?? null,
                'discount_value' => $item['discount_value'] ?? null,
                'discount_amount' => CollectionAllocationMath::normalize($item['discount_amount']),
                'subtotal' => CollectionAllocationMath::normalize($item['subtotal']),
                'tax_amount' => CollectionAllocationMath::normalize($item['tax_amount']),
                'total' => CollectionAllocationMath::normalize($item['total']),
            ]);
        }

        return $creditNote;
    }
}
