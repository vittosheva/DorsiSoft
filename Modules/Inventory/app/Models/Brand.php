<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class Brand extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'inv_brands';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'logo_url',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
