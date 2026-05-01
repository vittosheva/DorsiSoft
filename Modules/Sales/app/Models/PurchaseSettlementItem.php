<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseSettlementItem extends Model
{
    protected $table = 'sales_purchase_settlement_items';

    protected $fillable = [
        'purchase_settlement_id',
        'product_id',
        'product_code',
        'product_name',
        'product_unit',
        'sort_order',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:8',
            'discount_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function purchaseSettlement(): BelongsTo
    {
        return $this->belongsTo(PurchaseSettlement::class, 'purchase_settlement_id');
    }
}
