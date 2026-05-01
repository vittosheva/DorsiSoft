<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class Lot extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'inv_lots';

    protected $fillable = [
        'company_id',
        'product_id',
        'code',
        'expiry_date',
        'manufactured_date',
        'supplier_lot_code',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufactured_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'lot_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(SerialNumber::class, 'lot_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function expiringSoon(Builder $query, int $days = 30): void
    {
        $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::today()->addDays($days))
            ->where('expiry_date', '>=', Carbon::today());
    }
}
