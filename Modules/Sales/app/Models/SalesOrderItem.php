<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Product;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Models\Concerns\HasProductSnapshot;

final class SalesOrderItem extends Model
{
    use HasProductSnapshot;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_code',
        'product_name',
        'product_unit',
        'sort_order',
        'description',
        'detail_1',
        'detail_2',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'subtotal',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountTypeEnum::class,
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:8',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * FK para navegación — siempre usar product_code y product_name para mostrar.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(SalesOrderItemTax::class, 'order_item_id');
    }
}
