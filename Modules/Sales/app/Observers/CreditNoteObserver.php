<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Finance\Models\TaxApplication;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Inventory\Data\MoveInDTO;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Services\InventoryService;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Throwable;

final class CreditNoteObserver
{
    public function saving(CreditNote $creditNote): void
    {
        $this->syncCustomerSnapshot($creditNote);
    }

    public function creating(CreditNote $creditNote): void
    {
        $creditNote->status ??= CreditNoteStatusEnum::Draft;
    }

    public function updated(CreditNote $creditNote): void
    {
        if ($creditNote->isDirty('electronic_status') && $creditNote->electronic_status === ElectronicStatusEnum::Authorized) {
            $this->createInventoryMovements($creditNote);
            $this->createTaxApplications($creditNote);
        }

        if ($creditNote->isDirty('status') && $creditNote->status === CreditNoteStatusEnum::Voided) {
            TaxApplication::query()
                ->where('applicable_type', CreditNote::class)
                ->where('applicable_id', $creditNote->id)
                ->delete();
        }
    }

    public function created(CreditNote $creditNote): void
    {
        // Intentionally left blank: credit note balances are now applied explicitly through collections.
    }

    private function createInventoryMovements(CreditNote $creditNote): void
    {
        $creditNote->loadMissing('invoice');

        $warehouseId = $creditNote->invoice?->warehouse_id;

        if (blank($warehouseId)) {
            Log::warning('CreditNoteObserver: invoice has no warehouse_id, skipping inventory movements.', ['credit_note_id' => $creditNote->id]);

            return;
        }

        $docType = InventoryDocumentType::findByCode('DEVOLUCION_VENTA');

        if (! $docType) {
            Log::warning('CreditNoteObserver: document type DEVOLUCION_VENTA not found, skipping inventory movements.');

            return;
        }

        $creditNote->loadMissing('items');

        foreach ($creditNote->items as $item) {
            if (blank($item->product_id)) {
                continue;
            }

            try {
                app(InventoryService::class)->moveIn(new MoveInDTO(
                    companyId: $creditNote->company_id,
                    warehouseId: $warehouseId,
                    productId: $item->product_id,
                    documentTypeId: $docType->getKey(),
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->unit_price,
                    movementDate: now()->startOfDay(),
                    sourceType: CreditNote::class,
                    sourceId: $creditNote->id,
                    referenceCode: $creditNote->code,
                ));
            } catch (Throwable $e) {
                Log::error('CreditNoteObserver: failed to create inventory movement for item.', [
                    'credit_note_id' => $creditNote->id,
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createTaxApplications(CreditNote $creditNote): void
    {
        TaxApplication::query()
            ->where('applicable_type', CreditNote::class)
            ->where('applicable_id', $creditNote->id)
            ->delete();

        $creditNote->loadMissing(['items.taxes']);

        $companyId = $creditNote->company_id;
        $appliedAt = $creditNote->issue_date ?? now()->toDateString();

        foreach ($creditNote->items as $item) {
            foreach ($item->taxes as $taxLine) {
                TaxApplication::create([
                    'company_id' => $companyId,
                    'applicable_type' => CreditNote::class,
                    'applicable_id' => $creditNote->id,
                    'tax_id' => $taxLine->tax_id,
                    'tax_definition_id' => null,
                    'tax_type' => $taxLine->tax_type,
                    'sri_code' => $taxLine->tax_code,
                    'sri_percentage_code' => $taxLine->tax_percentage_code,
                    'base_amount' => $taxLine->base_amount,
                    'rate' => $taxLine->tax_rate,
                    'tax_amount' => $taxLine->tax_amount,
                    'calculation_type' => $taxLine->tax_calculation_type,
                    'snapshot' => [
                        'credit_note_id' => $creditNote->id,
                        'credit_note_code' => $creditNote->code,
                        'item_id' => $item->id,
                        'product_name' => $item->product_name ?? $item->description,
                        'tax_name' => $taxLine->tax_name,
                    ],
                    'applied_at' => $appliedAt,
                ]);
            }
        }
    }

    private function syncCustomerSnapshot(CreditNote $creditNote): void
    {
        if (blank($creditNote->business_partner_id)) {
            $creditNote->customer_name = null;
            $creditNote->customer_trade_name = null;
            $creditNote->customer_identification_type = null;
            $creditNote->customer_identification = null;
            $creditNote->customer_address = null;
            $creditNote->customer_email = null;
            $creditNote->customer_phone = null;

            return;
        }

        if (! $creditNote->isDirty('business_partner_id')
            && filled($creditNote->customer_name)
            && filled($creditNote->customer_identification_type)
            && filled($creditNote->customer_identification)
            && filled($creditNote->customer_address)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'trade_name', 'identification_type', 'identification_number', 'tax_address', 'email', 'phone', 'mobile'])
            ->find($creditNote->business_partner_id);

        if (! $bp) {
            return;
        }

        $creditNote->customer_name = $bp->legal_name;
        $creditNote->customer_trade_name = $bp->trade_name;
        $creditNote->customer_identification_type = $bp->identification_type;
        $creditNote->customer_identification = $bp->identification_number;
        $creditNote->customer_address = $bp->tax_address;
        $creditNote->customer_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
        $creditNote->customer_phone = $bp->phone ?? $bp->mobile;
    }

    private function syncInvoicesCreditedAmount(CreditNote $creditNote): void
    {
        $invoiceIds = array_values(array_unique(array_filter([
            $creditNote->invoice_id,
            $creditNote->getOriginal('invoice_id'),
        ])));

        foreach ($invoiceIds as $invoiceId) {
            $this->syncInvoiceCreditedAmountById($invoiceId);
        }
    }

    private function syncInvoiceCreditedAmountById(int|string $invoiceId): void
    {
        $invoice = Invoice::withoutGlobalScopes()->find($invoiceId);

        if (! $invoice) {
            return;
        }

        $this->syncInvoiceCreditedAmount($invoice);
    }

    private function syncInvoiceCreditedAmount(Invoice $invoice): void
    {
        $creditedAmount = CreditNote::withoutGlobalScopes()
            ->where('invoice_id', $invoice->getKey())
            ->whereNotIn('status', [CreditNoteStatusEnum::Voided->value, CreditNoteStatusEnum::Draft->value])
            ->where('electronic_status', ElectronicStatusEnum::Authorized->value)
            ->whereNull('deleted_at')
            ->sum('total');

        $invoice->credited_amount = CollectionAllocationMath::normalize($creditedAmount);

        $settled = bcadd(
            CollectionAllocationMath::normalize($invoice->paid_amount),
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
