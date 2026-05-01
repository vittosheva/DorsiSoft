<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Inventory\Exceptions\ImmutableMovementException;

final class InventoryMovement extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'inv_movements';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'destination_warehouse_id',
        'product_id',
        'document_type_id',
        'lot_id',
        'serial_id',
        'source_type',
        'source_id',
        'quantity',
        'unit_cost',
        'reference_code',
        'notes',
        'voided_at',
        'void_reason',
        'reversal_movement_id',
        'is_reversal',
        'movement_date',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:8',
            'is_reversal' => 'boolean',
            'voided_at' => 'datetime',
            'movement_date' => 'date',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(InventoryDocumentType::class, 'document_type_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class, 'serial_id');
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_movement_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function totalCost(): float
    {
        return (float) $this->quantity * (float) $this->unit_cost;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    #[Scope]
    public function forProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    #[Scope]
    public function forWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    #[Scope]
    public function inDateRange(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()]);
    }

    public function delete(): ?bool
    {
        throw new ImmutableMovementException('Inventory movements cannot be deleted. Use voidMovement() instead.');
    }
}
