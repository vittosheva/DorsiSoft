<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Events\StockBelowReorderPoint;
use Modules\Inventory\Models\InventoryBalance;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Product;

final class BalanceMaterializer
{
    public function update(int $companyId, int $warehouseId, int $productId, ?int $lotId, ?int $lastMovementId = null): void
    {
        $lotIdForQuery = $lotId;

        $aggregate = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($lotIdForQuery): void {
                if ($lotIdForQuery === null) {
                    $q->whereNull('lot_id');
                } else {
                    $q->where('lot_id', $lotIdForQuery);
                }
            })
            ->whereNull('voided_at')
            ->join('inv_document_types', 'inv_movements.document_type_id', '=', 'inv_document_types.id')
            ->selectRaw("
                SUM(CASE WHEN inv_document_types.movement_type IN ('in', 'adjustment') AND inv_movements.quantity > 0 THEN inv_movements.quantity
                         WHEN inv_document_types.movement_type IN ('out', 'transfer') THEN -inv_movements.quantity
                         WHEN inv_document_types.movement_type = 'adjustment' AND inv_movements.quantity < 0 THEN inv_movements.quantity
                         ELSE 0 END) as net_quantity,
                SUM(CASE WHEN inv_document_types.movement_type = 'in' THEN inv_movements.quantity * inv_movements.unit_cost ELSE 0 END) as total_in_cost,
                SUM(CASE WHEN inv_document_types.movement_type = 'in' THEN inv_movements.quantity ELSE 0 END) as total_in_qty
            ")
            ->first();

        $netQty = max(0.0, (float) ($aggregate->net_quantity ?? 0));
        $totalInCost = (float) ($aggregate->total_in_cost ?? 0);
        $totalInQty = (float) ($aggregate->total_in_qty ?? 0);
        $averageCost = $totalInQty > 0 ? $totalInCost / $totalInQty : 0.0;

        InventoryBalance::upsert(
            [
                [
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'lot_id' => $lotId,
                    'quantity_available' => $netQty,
                    'quantity_reserved' => DB::raw('COALESCE((SELECT SUM(quantity) FROM inv_reservations WHERE company_id = '.$companyId.' AND warehouse_id = '.$warehouseId.' AND product_id = '.$productId.' AND status IN (\'pending\',\'confirmed\')), 0)'),
                    'average_cost' => $averageCost,
                    'last_movement_id' => $lastMovementId,
                    'updated_at' => now(),
                ],
            ],
            uniqueBy: ['company_id', 'warehouse_id', 'product_id', 'lot_id'],
            update: ['quantity_available', 'average_cost', 'last_movement_id', 'updated_at'],
        );

        $this->invalidateCache($companyId, $warehouseId, $productId);
        $this->checkReorderPoint($companyId, $warehouseId, $productId, $netQty);
    }

    public function updateReservedQuantity(int $companyId, int $warehouseId, int $productId, ?int $lotId): void
    {
        $reserved = DB::table('inv_reservations')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('quantity');

        InventoryBalance::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($lotId): void {
                if ($lotId === null) {
                    $q->whereNull('lot_id');
                } else {
                    $q->where('lot_id', $lotId);
                }
            })
            ->update([
                'quantity_reserved' => (float) $reserved,
                'updated_at' => now(),
            ]);

        $this->invalidateCache($companyId, $warehouseId, $productId);
    }

    private function invalidateCache(int $companyId, int $warehouseId, int $productId): void
    {
        Cache::forget("inventory.balance.{$companyId}.{$warehouseId}.{$productId}");
        Cache::forget("inventory.value.{$companyId}");
    }

    private function checkReorderPoint(int $companyId, int $warehouseId, int $productId, float $currentQty): void
    {
        $product = Product::find($productId);

        if ($product === null || $product->reorder_point === null) {
            return;
        }

        if ($currentQty <= (float) $product->reorder_point) {
            StockBelowReorderPoint::dispatch($companyId, $warehouseId, $product, $currentQty);
        }
    }
}
