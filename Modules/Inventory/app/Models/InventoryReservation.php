<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Inventory\Enums\ReservationStatusEnum;

final class InventoryReservation extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'inv_reservations';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'lot_id',
        'serial_id',
        'quantity',
        'status',
        'source_type',
        'source_id',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'status' => ReservationStatusEnum::class,
            'expires_at' => 'datetime',
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

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class, 'serial_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->whereIn('status', [ReservationStatusEnum::Pending, ReservationStatusEnum::Confirmed]);
    }

    #[Scope]
    public function expired(Builder $query): void
    {
        $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('status', ReservationStatusEnum::Pending);
    }
}
