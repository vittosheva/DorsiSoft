<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Database\Factories\TaxDefinitionFactory;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Enums\TaxBaseTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\TaxNatureEnum;

final class TaxDefinition extends BaseModel
{
    use HasFactory;

    protected $table = 'sys_tax_definitions';

    protected $fillable = [
        'tax_catalog_id',
        'code',
        'name',
        'description',
        'tax_group',
        'tax_type',
        'applies_to',
        'rate',
        'fixed_amount',
        'calculation_type',
        'base_type',
        'is_exempt',
        'is_zero_rate',
        'is_withholding',
        'sri_code',
        'sri_percentage_code',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_group' => TaxGroupEnum::class,
            'tax_type' => TaxNatureEnum::class,
            'applies_to' => TaxAppliesToEnum::class,
            'calculation_type' => TaxCalculationTypeEnum::class,
            'base_type' => TaxBaseTypeEnum::class,
            'rate' => 'decimal:4',
            'fixed_amount' => 'decimal:4',
            'is_exempt' => 'boolean',
            'is_zero_rate' => 'boolean',
            'is_withholding' => 'boolean',
            'is_active' => 'boolean',
            'tax_catalog_id' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(TaxCatalog::class, 'tax_catalog_id');
    }

    public function withholdingRates(): HasMany
    {
        return $this->hasMany(TaxWithholdingRate::class, 'tax_definition_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class, 'tax_definition_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function byGroup(Builder $query, TaxGroupEnum|string $group): void
    {
        $query->where('tax_group', $group instanceof TaxGroupEnum ? $group->value : $group);
    }

    #[Scope]
    public function validAt(Builder $query, Carbon $date): void
    {
        $query->where('valid_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $date)
            );
    }

    protected static function newFactory(): Factory
    {
        return TaxDefinitionFactory::new();
    }
}
