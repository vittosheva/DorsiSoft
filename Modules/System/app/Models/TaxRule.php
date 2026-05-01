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
use Modules\Finance\Database\Factories\TaxRuleFactory;
use Modules\System\Enums\TaxAppliesToEnum;

final class TaxRule extends BaseModel
{
    use HasFactory;

    protected $table = 'sys_tax_rules';

    protected $fillable = [
        'name',
        'description',
        'applies_to',
        'priority',
        'conditions',
        'tax_definition_id',
        'is_active',
        'valid_from',
        'valid_to',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'applies_to' => TaxAppliesToEnum::class,
            'conditions' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function taxDefinition(): BelongsTo
    {
        return $this->belongsTo(TaxDefinition::class, 'tax_definition_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TaxRuleLine::class, 'tax_rule_id')->orderBy('sort_order');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
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

    #[Scope]
    public function forSales(Builder $query): void
    {
        $query->whereIn('applies_to', [TaxAppliesToEnum::Venta->value, TaxAppliesToEnum::Ambos->value]);
    }

    #[Scope]
    public function forPurchases(Builder $query): void
    {
        $query->whereIn('applies_to', [TaxAppliesToEnum::Compra->value, TaxAppliesToEnum::Ambos->value]);
    }

    protected static function newFactory(): Factory
    {
        return TaxRuleFactory::new();
    }
}
