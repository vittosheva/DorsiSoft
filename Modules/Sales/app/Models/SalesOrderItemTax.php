<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class SalesOrderItemTax extends Model
{
    protected $table = 'sales_order_item_taxes';

    protected $fillable = [
        'order_item_id',
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
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id');
    }

    /**
     * FK para navegación — siempre usar tax_name y tax_rate para mostrar.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
