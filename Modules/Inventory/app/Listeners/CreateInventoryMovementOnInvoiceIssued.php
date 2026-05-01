<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Inventory\Data\MoveOutDTO;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;
use Modules\Sales\Events\InvoiceIssued;

final class CreateInventoryMovementOnInvoiceIssued
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice;
        $invoice->loadMissing(['items.product']);

        $docType = InventoryDocumentType::findByCode('VENTA');

        if ($docType === null) {
            Log::warning('Inventory document type VENTA not found. Cannot create inventory movements for invoice #'.$invoice->getKey());

            return;
        }

        $warehouse = Warehouse::where('company_id', $invoice->company_id)
            ->where('is_default', true)
            ->first();

        if ($warehouse === null) {
            Log::warning('No default warehouse found for company '.$invoice->company_id.'. Cannot create inventory movements for invoice #'.$invoice->getKey());

            return;
        }

        foreach ($invoice->items as $item) {
            if ($item->product === null) {
                continue;
            }

            if (! $item->product->is_inventory) {
                continue;
            }

            try {
                $this->inventoryService->moveOut(new MoveOutDTO(
                    companyId: $invoice->company_id,
                    warehouseId: $warehouse->getKey(),
                    productId: $item->product->getKey(),
                    documentTypeId: $docType->getKey(),
                    quantity: (float) $item->quantity,
                    movementDate: $invoice->issue_date,
                    sourceType: $invoice->getMorphClass(),
                    sourceId: $invoice->getKey(),
                    referenceCode: $invoice->code,
                    notes: null,
                ));
            } catch (InsufficientStockException $e) {
                Log::warning('Insufficient stock on invoice issuance.', [
                    'invoice_id' => $invoice->getKey(),
                    'product_id' => $item->product->getKey(),
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
