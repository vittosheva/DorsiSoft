<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Enums\TaxCategoryEnum;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;
use Modules\System\Models\TaxDefinition;

final class Tax extends BaseModel
{
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'fin_taxes';

    protected $fillable = [
        'company_id',
        'tax_definition_id',
        'code',
        'name',
        'type',
        'sri_code',
        'sri_percentage_code',
        'rate',
        'tax_category',
        'tax_catalog_version',
        'calculation_type',
        'description',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaxTypeEnum::class,
            'rate' => 'decimal:4',
            'tax_category' => TaxCategoryEnum::class,
            'tax_catalog_version' => 'string',
            'calculation_type' => TaxCalculationTypeEnum::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sri_code' => 'integer',
            'sri_percentage_code' => 'integer',
            'tax_definition_id' => 'integer',
        ];
    }

    public static function getCodePrefix(): string
    {
        return 'IMP';
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(TaxDefinition::class, 'tax_definition_id');
    }

    public function getCodeScope(): array
    {
        return $this->company_id ? ['company_id' => $this->company_id] : [];
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function byType(Builder $query, TaxTypeEnum|string $type): void
    {
        $query->where('type', $type instanceof TaxTypeEnum ? $type->value : $type);
    }

    public function isZeroButTaxable(): bool
    {
        return $this->rate === 0
            && $this->tax_category === 'taxable'
            && $this->type === TaxTypeEnum::Iva;
    }

    protected static function booted(): void
    {
        self::saving(function (self $tax): void {
            if (! $tax->is_default) {
                return;
            }

            // 👇 SOLO aplica a IVA
            if ($tax->type !== TaxTypeEnum::Iva->value) {
                return;
            }

            $tax->is_active = true;

            $type = $tax->type instanceof TaxTypeEnum ? $tax->type->value : (string) $tax->type;

            self::query()
                ->where('company_id', $tax->company_id)
                ->where('type', $type)
                ->when(
                    $tax->exists,
                    fn (Builder $query): Builder => $query->whereKeyNot($tax->getKey()),
                )
                ->update(['is_default' => false]);
        });
    }
}
