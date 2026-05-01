<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Inventory\Enums\SerialStatusEnum;

final class SerialNumber extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'inv_serials';

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'lot_id',
        'serial_number',
        'status',
        'sold_at',
        'sold_movement_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SerialStatusEnum::class,
            'sold_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    #[Scope]
    public function available(Builder $query): void
    {
        $query->where('status', SerialStatusEnum::Available);
    }

    #[Scope]
    public function inWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }
}
