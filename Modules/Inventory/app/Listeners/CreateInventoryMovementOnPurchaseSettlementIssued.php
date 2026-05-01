<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Inventory\Data\MoveInDTO;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;
use Modules\Sales\Events\PurchaseSettlementIssued;

final class CreateInventoryMovementOnPurchaseSettlementIssued
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function handle(PurchaseSettlementIssued $event): void
    {
        $settlement = $event->purchaseSettlement;
        $settlement->loadMissing(['items.product']);

        $docType = InventoryDocumentType::findByCode('COMPRA');

        if ($docType === null) {
            Log::warning('Inventory document type COMPRA not found. Cannot create inventory movements for settlement #'.$settlement->getKey());

            return;
        }

        $warehouse = Warehouse::where('company_id', $settlement->company_id)
            ->where('is_default', true)
            ->first();

        if ($warehouse === null) {
            Log::warning('No default warehouse for company '.$settlement->company_id);

            return;
        }

        foreach ($settlement->items as $item) {
            if ($item->product === null || ! $item->product->is_inventory) {
                continue;
            }

            $this->inventoryService->moveIn(new MoveInDTO(
                companyId: $settlement->company_id,
                warehouseId: $warehouse->getKey(),
                productId: $item->product->getKey(),
                documentTypeId: $docType->getKey(),
                quantity: (float) $item->quantity,
                unitCost: (float) $item->unit_price,
                movementDate: $settlement->issue_date,
                sourceType: $settlement->getMorphClass(),
                sourceId: $settlement->getKey(),
                referenceCode: $settlement->code,
                notes: null,
            ));
        }
    }
}
