<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Finance\Models\TaxApplication;
use Modules\Inventory\Data\MoveInDTO;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Services\InventoryService;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Throwable;

final class PurchaseSettlementObserver
{
    public function saving(PurchaseSettlement $settlement): void
    {
        $this->syncSupplierSnapshot($settlement);
    }

    public function updated(PurchaseSettlement $settlement): void
    {
        if ($settlement->isDirty('electronic_status') && $settlement->electronic_status === ElectronicStatusEnum::Authorized) {
            $this->createInventoryMovements($settlement);
            $this->createTaxApplications($settlement);
        }
    }

    private function createInventoryMovements(PurchaseSettlement $settlement): void
    {
        if (blank($settlement->warehouse_id)) {
            Log::warning('PurchaseSettlementObserver: settlement has no warehouse_id, skipping inventory movements.', ['settlement_id' => $settlement->id]);

            return;
        }

        $docType = InventoryDocumentType::findByCode('COMPRA');

        if (! $docType) {
            Log::warning('PurchaseSettlementObserver: document type COMPRA not found, skipping inventory movements.');

            return;
        }

        $settlement->loadMissing('items');

        foreach ($settlement->items as $item) {
            if (blank($item->product_id)) {
                continue;
            }

            try {
                app(InventoryService::class)->moveIn(new MoveInDTO(
                    companyId: $settlement->company_id,
                    warehouseId: $settlement->warehouse_id,
                    productId: $item->product_id,
                    documentTypeId: $docType->getKey(),
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->unit_price,
                    movementDate: now()->startOfDay(),
                    sourceType: PurchaseSettlement::class,
                    sourceId: $settlement->id,
                    referenceCode: $settlement->code,
                ));
            } catch (Throwable $e) {
                Log::error('PurchaseSettlementObserver: failed to create inventory movement for item.', [
                    'settlement_id' => $settlement->id,
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncSupplierSnapshot(PurchaseSettlement $settlement): void
    {
        if (blank($settlement->supplier_id)) {
            $settlement->supplier_name = null;
            $settlement->supplier_identification_type = null;
            $settlement->supplier_identification = null;
            $settlement->supplier_address = null;
            $settlement->supplier_email = null;

            return;
        }

        if (! $settlement->isDirty('supplier_id')
            && filled($settlement->supplier_name)
            && filled($settlement->supplier_identification_type)
            && filled($settlement->supplier_identification)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'identification_type', 'identification_number', 'tax_address', 'email'])
            ->find($settlement->supplier_id);

        if (! $bp) {
            return;
        }

        $settlement->supplier_name = $bp->legal_name;
        $settlement->supplier_identification_type = $bp->identification_type;
        $settlement->supplier_identification = $bp->identification_number;
        $settlement->supplier_address = $bp->tax_address;
        $settlement->supplier_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
    }

    private function createTaxApplications(PurchaseSettlement $settlement): void
    {
        TaxApplication::query()
            ->where('applicable_type', PurchaseSettlement::class)
            ->where('applicable_id', $settlement->id)
            ->delete();

        $settlement->loadMissing('items');

        $companyId = $settlement->company_id;
        $appliedAt = $settlement->issue_date?->toDateString() ?? now()->toDateString();

        foreach ($settlement->items as $item) {
            if (bccomp((string) $item->tax_amount, '0', 4) <= 0) {
                continue;
            }

            $subtotal = (string) $item->subtotal;
            $taxAmount = (string) $item->tax_amount;
            $rate = bccomp($subtotal, '0', 4) > 0
                ? bcdiv(bcmul($taxAmount, '100', 8), $subtotal, 4)
                : '0.0000';

            TaxApplication::create([
                'company_id' => $companyId,
                'applicable_type' => PurchaseSettlement::class,
                'applicable_id' => $settlement->id,
                'tax_id' => null,
                'tax_definition_id' => null,
                'tax_type' => 'IVA',
                'sri_code' => '2',
                'sri_percentage_code' => null,
                'base_amount' => $subtotal,
                'rate' => $rate,
                'tax_amount' => $taxAmount,
                'calculation_type' => TaxCalculationTypeEnum::Percentage->value,
                'snapshot' => [
                    'settlement_id' => $settlement->id,
                    'settlement_code' => $settlement->code,
                    'item_id' => $item->id,
                    'product_name' => $item->product_name ?? $item->description,
                ],
                'applied_at' => $appliedAt,
            ]);
        }
    }
}
