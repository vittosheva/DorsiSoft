<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Traits\HasTenancy;

final class InventoryBalance extends Model
{
    use HasTenancy;

    public $timestamps = false;

    protected $table = 'inv_balances';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'lot_id',
        'quantity_available',
        'quantity_reserved',
        'average_cost',
        'last_movement_id',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'decimal:6',
            'quantity_reserved' => 'decimal:6',
            'average_cost' => 'decimal:8',
            'updated_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function lastMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'last_movement_id');
    }

    public function quantityOnHand(): float
    {
        return (float) $this->quantity_available + (float) $this->quantity_reserved;
    }

    public function totalValue(): float
    {
        return (float) $this->quantity_available * (float) $this->average_cost;
    }

    #[Scope]
    public function forWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    #[Scope]
    public function forProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    #[Scope]
    public function belowReorderPoint(Builder $query): void
    {
        $query->join('inv_products', 'inv_balances.product_id', '=', 'inv_products.id')
            ->whereNotNull('inv_products.reorder_point')
            ->whereRaw('inv_balances.quantity_available <= inv_products.reorder_point');
    }
}
