<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Support\Models\BaseModel;
use Modules\System\Enums\TaxGroupEnum;

final class TaxCatalog extends BaseModel
{
    protected $table = 'sys_tax_catalogs';

    protected $fillable = [
        'code',
        'name',
        'tax_group',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_group' => TaxGroupEnum::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function definitions(): HasMany
    {
        return $this->hasMany(TaxDefinition::class, 'tax_catalog_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
