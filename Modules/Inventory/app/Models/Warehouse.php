<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Establishment;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class Warehouse extends BaseModel
{
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'inv_warehouses';

    protected $fillable = [
        'company_id',
        'establishment_id',
        'code',
        'name',
        'address',
        'is_default',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'BOD';
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'warehouse_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'warehouse_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'warehouse_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function default(Builder $query): void
    {
        $query->where('is_default', true);
    }
}
