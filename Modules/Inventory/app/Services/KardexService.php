<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\InventoryMovement;

final class KardexService
{
    /**
     * Returns ordered kardex entries for a product+warehouse with running balance.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function kardex(?int $productId, ?int $warehouseId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = InventoryMovement::query()
            ->with(['documentType', 'lot', 'creator'])
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('voided_at')
            ->join('inv_document_types', 'inv_movements.document_type_id', '=', 'inv_document_types.id')
            ->select('inv_movements.*', 'inv_document_types.movement_type', 'inv_document_types.name as doc_type_name')
            ->orderBy('inv_movements.movement_date')
            ->orderBy('inv_movements.id');

        if ($from !== null) {
            $query->where('inv_movements.movement_date', '>=', $from->toDateString());
        }

        if ($to !== null) {
            $query->where('inv_movements.movement_date', '<=', $to->toDateString());
        }

        $movements = $query->get();

        $runningBalance = 0.0;
        $runningCost = 0.0;

        return $movements->map(function ($movement) use (&$runningBalance, &$runningCost): array {
            $qty = (float) $movement->quantity;
            $cost = (float) $movement->unit_cost;
            $type = $movement->movement_type;

            $inQty = 0.0;
            $outQty = 0.0;

            if (in_array($type, ['in', 'adjustment']) && $qty >= 0) {
                $inQty = $qty;
                $runningBalance += $qty;
                // WAC recalculation
                $totalStock = $runningBalance;
                $totalCost = $runningCost + ($qty * $cost);
                $runningCost = $totalCost;
            } elseif (in_array($type, ['out', 'transfer']) || $qty < 0) {
                $outQty = abs($qty);
                $runningBalance -= $outQty;
                $runningCost = $runningBalance > 0
                    ? $runningCost * ($runningBalance / ($runningBalance + $outQty))
                    : 0.0;
            }

            $averageCost = $runningBalance > 0 ? $runningCost / $runningBalance : 0.0;

            return [
                'id' => $movement->id,
                'movement_date' => $movement->movement_date,
                'reference_code' => $movement->reference_code,
                'document_type' => $movement->doc_type_name,
                'movement_type' => $type,
                'lot_code' => $movement->lot?->code,
                'in_quantity' => $inQty,
                'out_quantity' => $outQty,
                'unit_cost' => $cost,
                'balance_quantity' => max(0.0, $runningBalance),
                'average_cost' => $averageCost,
                'balance_value' => max(0.0, $runningBalance) * $averageCost,
                'notes' => $movement->notes,
            ];
        });
    }

    public function averageCost(int $productId, int $warehouseId): float
    {
        $result = InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('voided_at')
            ->join('inv_document_types', 'inv_movements.document_type_id', '=', 'inv_document_types.id')
            ->where('inv_document_types.movement_type', 'in')
            ->selectRaw('SUM(quantity * unit_cost) / NULLIF(SUM(quantity), 0) as wac')
            ->value('wac');

        return (float) $result;
    }

    /**
     * Returns FIFO cost layers (for lot-tracked products).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fifoLayers(int $productId, int $warehouseId): Collection
    {
        return InventoryMovement::query()
            ->with('lot')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('voided_at')
            ->whereNotNull('lot_id')
            ->join('inv_document_types', 'inv_movements.document_type_id', '=', 'inv_document_types.id')
            ->where('inv_document_types.movement_type', 'in')
            ->select('inv_movements.*')
            ->orderBy('inv_movements.movement_date')
            ->orderBy('inv_movements.id')
            ->get()
            ->map(fn ($m) => [
                'movement_id' => $m->id,
                'lot_id' => $m->lot_id,
                'lot_code' => $m->lot?->code,
                'expiry_date' => $m->lot?->expiry_date,
                'quantity' => (float) $m->quantity,
                'unit_cost' => (float) $m->unit_cost,
                'movement_date' => $m->movement_date,
            ]);
    }
}
