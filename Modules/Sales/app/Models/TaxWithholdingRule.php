<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class TaxWithholdingRule extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'sales_tax_withholding_rules';

    protected $fillable = [
        'company_id',
        'type',
        'concept',
        'percentage',
        'account',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(TaxWithholdingRuleChange::class, 'rule_id');
    }

    protected static function booted(): void
    {
        self::updating(function (self $model): void {
            if ($model->isDirty('percentage')) {
                $old = $model->getOriginal('percentage');
                $new = $model->percentage;

                // record change
                $model->changes()->create([
                    'old_percentage' => $old,
                    'new_percentage' => $new,
                ]);
            }
        });
    }
}
