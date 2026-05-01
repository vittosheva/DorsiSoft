<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Finance\Models\TaxApplication;
use Modules\Inventory\Data\MoveOutDTO;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryService;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Throwable;

final class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('electronic_status') && $invoice->electronic_status === ElectronicStatusEnum::Authorized) {
            $this->createTaxApplications($invoice);
            $this->createInventoryMovements($invoice);
        }

        if ($invoice->isDirty('status') && $invoice->status === InvoiceStatusEnum::Voided) {
            $this->deleteTaxApplications(Invoice::class, $invoice->id);
            $this->voidInventoryMovements($invoice);
        }
    }

    public function saving(Invoice $invoice): void
    {
        $this->syncCustomerSnapshot($invoice);
        $this->syncSellerSnapshot($invoice);
    }

    private function deleteTaxApplications(string $type, int $id): void
    {
        TaxApplication::query()
            ->where('applicable_type', $type)
            ->where('applicable_id', $id)
            ->delete();
    }

    private function createTaxApplications(Invoice $invoice): void
    {
        $this->deleteTaxApplications(Invoice::class, $invoice->id);

        $invoice->loadMissing(['items.taxes']);

        $companyId = $invoice->company_id;
        $appliedAt = $invoice->issue_date ?? now()->toDateString();

        foreach ($invoice->items as $item) {
            foreach ($item->taxes as $taxLine) {
                TaxApplication::create([
                    'company_id' => $companyId,
                    'applicable_type' => Invoice::class,
                    'applicable_id' => $invoice->id,
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
                        'invoice_id' => $invoice->id,
                        'invoice_code' => $invoice->code,
                        'item_id' => $item->id,
                        'product_name' => $item->product_name ?? $item->description,
                        'tax_name' => $taxLine->tax_name,
                    ],
                    'applied_at' => $appliedAt,
                ]);
            }
        }
    }

    private function createInventoryMovements(Invoice $invoice): void
    {
        if (blank($invoice->warehouse_id)) {
            Log::warning('InvoiceObserver: invoice has no warehouse_id, skipping inventory movements.', ['invoice_id' => $invoice->id]);

            return;
        }

        $docType = InventoryDocumentType::findByCode('VENTA');

        if (! $docType) {
            Log::warning('InvoiceObserver: document type VENTA not found, skipping inventory movements.');

            return;
        }

        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            if (blank($item->product_id)) {
                continue;
            }

            try {
                app(InventoryService::class)->moveOut(new MoveOutDTO(
                    companyId: $invoice->company_id,
                    warehouseId: $invoice->warehouse_id,
                    productId: $item->product_id,
                    documentTypeId: $docType->getKey(),
                    quantity: (float) $item->quantity,
                    movementDate: now()->startOfDay(),
                    sourceType: Invoice::class,
                    sourceId: $invoice->id,
                    referenceCode: $invoice->code,
                ));
            } catch (Throwable $e) {
                Log::error('InvoiceObserver: failed to create inventory movement for item.', [
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function voidInventoryMovements(Invoice $invoice): void
    {
        $movements = InventoryMovement::where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->active()
            ->get();

        foreach ($movements as $movement) {
            try {
                app(InventoryService::class)->voidMovement(
                    $movement,
                    'Factura anulada',
                    (int) ($invoice->updated_by ?? $invoice->created_by),
                );
            } catch (Throwable $e) {
                Log::error('InvoiceObserver: failed to void inventory movement.', [
                    'invoice_id' => $invoice->id,
                    'movement_id' => $movement->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncCustomerSnapshot(Invoice $invoice): void
    {
        if (blank($invoice->business_partner_id)) {
            $invoice->customer_name = null;
            $invoice->customer_trade_name = null;
            $invoice->customer_identification_type = null;
            $invoice->customer_identification = null;
            $invoice->customer_address = null;
            $invoice->customer_email = null;
            $invoice->customer_phone = null;

            return;
        }

        if (! $invoice->isDirty('business_partner_id')
            && filled($invoice->customer_name)
            && filled($invoice->customer_identification_type)
            && filled($invoice->customer_identification)
            && filled($invoice->customer_address)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'trade_name', 'identification_type', 'identification_number', 'tax_address', 'email', 'phone', 'mobile'])
            ->find($invoice->business_partner_id);

        if (! $bp) {
            return;
        }

        $invoice->customer_name = $bp->legal_name;
        $invoice->customer_trade_name = $bp->trade_name;
        $invoice->customer_identification_type = $bp->identification_type;
        $invoice->customer_identification = $bp->identification_number;
        $invoice->customer_address = $bp->tax_address;
        $invoice->customer_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
        $invoice->customer_phone = $bp->phone ?? $bp->mobile;
    }

    private function syncSellerSnapshot(Invoice $invoice): void
    {
        if (! $invoice->isDirty('seller_id') && filled($invoice->seller_name)) {
            return;
        }

        $invoice->seller_name = blank($invoice->seller_id)
            ? null
            : User::query()->whereKey($invoice->seller_id)->value('name');
    }
}
