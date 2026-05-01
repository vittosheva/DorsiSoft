<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Support\Models\BaseModel;
use Modules\Sales\Models\WithholdingItem;
use Modules\System\Enums\WithholdingAppliesToEnum;

final class TaxWithholdingRate extends BaseModel
{
    use HasFactory;

    protected $table = 'sys_tax_withholding_rates';

    protected $fillable = [
        'tax_definition_id',
        'percentage',
        'sri_code',
        'description',
        'applies_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'applies_to' => WithholdingAppliesToEnum::class,
            'is_active' => 'boolean',
        ];
    }

    public function taxDefinition(): BelongsTo
    {
        return $this->belongsTo(TaxDefinition::class, 'tax_definition_id');
    }

    public function withholdingItems(): HasMany
    {
        return $this->hasMany(WithholdingItem::class, 'withholding_rate_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function forGoods(Builder $query): void
    {
        $query->whereIn('applies_to', [
            WithholdingAppliesToEnum::Bien->value,
            WithholdingAppliesToEnum::Ambos->value,
        ]);
    }

    #[Scope]
    public function forServices(Builder $query): void
    {
        $query->whereIn('applies_to', [
            WithholdingAppliesToEnum::Servicio->value,
            WithholdingAppliesToEnum::Ambos->value,
        ]);
    }
}
