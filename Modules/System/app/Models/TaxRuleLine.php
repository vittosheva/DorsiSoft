<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Support\Models\BaseModel;

final class TaxRuleLine extends BaseModel
{
    protected $table = 'sys_tax_rule_lines';

    protected $fillable = [
        'tax_rule_id',
        'sort_order',
        'from_amount',
        'to_amount',
        'rate',
        'fixed_amount',
        'excess_from',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'from_amount' => 'decimal:4',
            'to_amount' => 'decimal:4',
            'rate' => 'decimal:4',
            'fixed_amount' => 'decimal:4',
            'excess_from' => 'decimal:4',
        ];
    }

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class, 'tax_rule_id');
    }
}
