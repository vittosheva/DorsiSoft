<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\Models\TaxWithholdingRate;

final class WithholdingItem extends Model
{
    protected $table = 'sales_withholding_items';

    protected $fillable = [
        'withholding_id',
        'withholding_rate_id',
        'tax_type',
        'tax_code',
        'tax_rate',
        'base_amount',
        'withheld_amount',
        'source_document_type',
        'source_document_number',
        'source_document_date',
        'source_purchase_settlement_id',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'base_amount' => 'decimal:4',
            'withheld_amount' => 'decimal:4',
            'source_document_date' => 'date',
        ];
    }

    public function withholding(): BelongsTo
    {
        return $this->belongsTo(Withholding::class, 'withholding_id');
    }

    public function withholdingRate(): BelongsTo
    {
        return $this->belongsTo(TaxWithholdingRate::class, 'withholding_rate_id')->withoutGlobalScopes();
    }
}
