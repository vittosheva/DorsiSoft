<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class DebitNoteItemTax extends Model
{
    protected $table = 'sales_debit_note_item_taxes';

    protected $fillable = [
        'debit_note_item_id',
        'tax_id',
        'tax_name',
        'tax_type',
        'tax_code',
        'tax_percentage_code',
        'tax_rate',
        'tax_calculation_type',
        'base_amount',
        'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'tax_calculation_type' => TaxCalculationTypeEnum::class,
            'base_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DebitNoteItem::class, 'debit_note_item_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id')->withoutGlobalScopes();
    }
}
