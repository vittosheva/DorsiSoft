<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Data\AdjustDTO;
use Modules\Inventory\Data\MoveInDTO;
use Modules\Inventory\Data\MoveOutDTO;
use Modules\Inventory\Data\TransferDTO;
use Modules\Inventory\Enums\SerialStatusEnum;
use Modules\Inventory\Events\InventoryMovementCreated;
use Modules\Inventory\Events\InventoryMovementVoided;
use Modules\Inventory\Exceptions\DuplicateSerialException;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Inventory\Models\InventoryBalance;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\SerialNumber;

final class InventoryService
{
    public function __construct(
        private readonly BalanceMaterializer $materializer,
    ) {}

    public function moveIn(MoveInDTO $dto): InventoryMovement
    {
        return DB::transaction(function () use ($dto): InventoryMovement {
            if ($dto->serialNumbers !== []) {
                $existing = SerialNumber::where('company_id', $dto->companyId)
                    ->where('product_id', $dto->productId)
                    ->whereIn('serial_number', $dto->serialNumbers)
                    ->value('serial_number');

                if ($existing !== null) {
                    throw new DuplicateSerialException($existing);
                }
            }

            $movement = InventoryMovement::create([
                'company_id' => $dto->companyId,
                'warehouse_id' => $dto->warehouseId,
                'product_id' => $dto->productId,
                'document_type_id' => $dto->documentTypeId,
                'lot_id' => $dto->lotId,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId,
                'quantity' => $dto->quantity,
                'unit_cost' => $dto->unitCost,
                'reference_code' => $dto->referenceCode,
                'notes' => $dto->notes,
                'movement_date' => $dto->movementDate->toDateString(),
            ]);

            if ($dto->serialNumbers !== []) {
                $now = now();
                SerialNumber::insert(array_map(fn (string $sn): array => [
                    'company_id' => $dto->companyId,
                    'product_id' => $dto->productId,
                    'warehouse_id' => $dto->warehouseId,
                    'lot_id' => $dto->lotId,
                    'serial_number' => $sn,
                    'status' => SerialStatusEnum::Available->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $dto->serialNumbers));
            }

            $this->materializer->update(
                $dto->companyId,
                $dto->warehouseId,
                $dto->productId,
                $dto->lotId,
                $movement->getKey(),
            );

            InventoryMovementCreated::dispatch($movement);

            return $movement;
        });
    }

    public function moveOut(MoveOutDTO $dto): InventoryMovement
    {
        return DB::transaction(function () use ($dto): InventoryMovement {
            $this->assertSufficientStock(
                $dto->companyId,
                $dto->warehouseId,
                $dto->productId,
                $dto->lotId,
                $dto->quantity,
            );

            $unitCost = $this->resolveUnitCost($dto->companyId, $dto->warehouseId, $dto->productId, $dto->lotId);

            $movement = InventoryMovement::create([
                'company_id' => $dto->companyId,
                'warehouse_id' => $dto->warehouseId,
                'product_id' => $dto->productId,
                'document_type_id' => $dto->documentTypeId,
                'lot_id' => $dto->lotId,
                'serial_id' => $dto->serialId,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId,
                'quantity' => $dto->quantity,
                'unit_cost' => $unitCost,
                'reference_code' => $dto->referenceCode,
                'notes' => $dto->notes,
                'movement_date' => $dto->movementDate->toDateString(),
            ]);

            if ($dto->serialId !== null) {
                SerialNumber::where('id', $dto->serialId)->update([
                    'status' => SerialStatusEnum::Sold,
                    'sold_at' => now(),
                    'sold_movement_id' => $movement->getKey(),
                ]);
            }

            $this->materializer->update(
                $dto->companyId,
                $dto->warehouseId,
                $dto->productId,
                $dto->lotId,
                $movement->getKey(),
            );

            InventoryMovementCreated::dispatch($movement);

            return $movement;
        });
    }

    /**
     * @return array{0: InventoryMovement, 1: InventoryMovement}
     */
    public function transfer(TransferDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {
            $this->assertSufficientStock(
                $dto->companyId,
                $dto->fromWarehouseId,
                $dto->productId,
                $dto->lotId,
                $dto->quantity,
            );

            $outDocType = InventoryDocumentType::findByCode('TRANSFERENCIA');

            $unitCost = $this->resolveUnitCost(
                $dto->companyId, $dto->fromWarehouseId, $dto->productId, $dto->lotId
            );

            $outMovement = InventoryMovement::create([
                'company_id' => $dto->companyId,
                'warehouse_id' => $dto->fromWarehouseId,
                'destination_warehouse_id' => $dto->toWarehouseId,
                'product_id' => $dto->productId,
                'document_type_id' => $dto->documentTypeId,
                'lot_id' => $dto->lotId,
                'serial_id' => $dto->serialId,
                'quantity' => $dto->quantity,
                'unit_cost' => $unitCost,
                'reference_code' => $dto->referenceCode,
                'notes' => $dto->notes,
                'movement_date' => $dto->movementDate->toDateString(),
            ]);

            $inMovement = InventoryMovement::create([
                'company_id' => $dto->companyId,
                'warehouse_id' => $dto->toWarehouseId,
                'product_id' => $dto->productId,
                'document_type_id' => $dto->documentTypeId,
                'lot_id' => $dto->lotId,
                'serial_id' => $dto->serialId,
                'quantity' => $dto->quantity,
                'unit_cost' => $unitCost,
                'reference_code' => $dto->referenceCode,
                'notes' => $dto->notes,
                'movement_date' => $dto->movementDate->toDateString(),
            ]);

            $this->materializer->update(
                $dto->companyId, $dto->fromWarehouseId, $dto->productId, $dto->lotId, $outMovement->getKey()
            );

            $this->materializer->update(
                $dto->companyId, $dto->toWarehouseId, $dto->productId, $dto->lotId, $inMovement->getKey()
            );

            InventoryMovementCreated::dispatch($outMovement);
            InventoryMovementCreated::dispatch($inMovement);

            return [$outMovement, $inMovement];
        });
    }

    public function adjust(AdjustDTO $dto): InventoryMovement
    {
        return DB::transaction(function () use ($dto): InventoryMovement {
            $qty = abs($dto->quantityDelta);

            $movement = InventoryMovement::create([
                'company_id' => $dto->companyId,
                'warehouse_id' => $dto->warehouseId,
                'product_id' => $dto->productId,
                'document_type_id' => $dto->documentTypeId,
                'lot_id' => $dto->lotId,
                'quantity' => $qty,
                'unit_cost' => $dto->unitCost,
                'reference_code' => $dto->referenceCode,
                'notes' => $dto->notes,
                'movement_date' => $dto->movementDate->toDateString(),
            ]);

            $this->materializer->update(
                $dto->companyId,
                $dto->warehouseId,
                $dto->productId,
                $dto->lotId,
                $movement->getKey(),
            );

            InventoryMovementCreated::dispatch($movement);

            return $movement;
        });
    }

    public function voidMovement(InventoryMovement $movement, string $reason, int $userId): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $reason, $userId): InventoryMovement {
            $movement->update([
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $reversal = InventoryMovement::create([
                'company_id' => $movement->company_id,
                'warehouse_id' => $movement->warehouse_id,
                'destination_warehouse_id' => $movement->destination_warehouse_id,
                'product_id' => $movement->product_id,
                'document_type_id' => $movement->document_type_id,
                'lot_id' => $movement->lot_id,
                'serial_id' => $movement->serial_id,
                'source_type' => $movement->source_type,
                'source_id' => $movement->source_id,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'reference_code' => 'VOID-'.$movement->reference_code,
                'notes' => $reason,
                'reversal_movement_id' => $movement->getKey(),
                'is_reversal' => true,
                'movement_date' => Carbon::today()->toDateString(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $movement->update(['reversal_movement_id' => $reversal->getKey()]);

            $this->materializer->update(
                $movement->company_id,
                $movement->warehouse_id,
                $movement->product_id,
                $movement->lot_id,
            );

            InventoryMovementVoided::dispatch($movement, $reversal);

            return $reversal;
        });
    }

    private function assertSufficientStock(
        int $companyId,
        int $warehouseId,
        int $productId,
        ?int $lotId,
        float $requested,
    ): void {
        $balance = InventoryBalance::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($lotId): void {
                if ($lotId === null) {
                    $q->whereNull('lot_id');
                } else {
                    $q->where('lot_id', $lotId);
                }
            })
            ->first();

        $available = $balance ? (float) $balance->quantity_available : 0.0;

        if ($available < $requested) {
            throw new InsufficientStockException($productId, $warehouseId, $requested, $available);
        }
    }

    private function resolveUnitCost(int $companyId, int $warehouseId, int $productId, ?int $lotId): float
    {
        $balance = InventoryBalance::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($lotId): void {
                if ($lotId === null) {
                    $q->whereNull('lot_id');
                } else {
                    $q->where('lot_id', $lotId);
                }
            })
            ->value('average_cost');

        return (float) $balance;
    }
}
