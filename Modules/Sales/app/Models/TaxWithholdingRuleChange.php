<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Support\Models\BaseModel;

final class TaxWithholdingRuleChange extends BaseModel
{
    use HasFactory;
    use Userstamps;

    protected $table = 'sales_tax_withholding_rule_changes';

    protected $fillable = [
        'rule_id',
        'old_percentage',
        'new_percentage',
        'reason',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TaxWithholdingRule::class, 'rule_id');
    }
}
