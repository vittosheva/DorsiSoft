<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasActiveScope;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class PriceList extends BaseModel
{
    use HasActiveScope;
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'sales_price_lists';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'currency_code',
        'start_date',
        'end_date',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'LP';
    }

    public static function hasDefaultForCompany(?int $companyId, ?int $ignoreId = null): bool
    {
        if ($companyId === null) {
            return false;
        }

        return self::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }
}
