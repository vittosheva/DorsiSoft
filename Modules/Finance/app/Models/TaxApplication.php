<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\Finance\Enums\TaxTypeEnum;

final class TaxApplication extends BaseModel
{
    use HasTenancy;
    use Userstamps;

    protected $table = 'fin_tax_applications';

    protected $fillable = [
        'company_id',
        'applicable_type',
        'applicable_id',
        'tax_id',
        'tax_definition_id',
        'tax_type',
        'sri_code',
        'sri_percentage_code',
        'base_amount',
        'rate',
        'tax_amount',
        'calculation_type',
        'snapshot',
        'applied_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:4',
            'rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'snapshot' => 'array',
            'applied_at' => 'date',
        ];
    }

    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function taxDefinition(): BelongsTo
    {
        return $this->belongsTo(TaxDefinition::class, 'tax_definition_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    #[Scope]
    public function byType(Builder $query, TaxTypeEnum $type): void
    {
        $query->where('tax_type', $type->value);
    }

    #[Scope]
    public function forPeriod(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('applied_at', [$from->toDateString(), $to->toDateString()]);
    }
}
