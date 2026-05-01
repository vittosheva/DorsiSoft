<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Support\Models\BaseModel;
use Modules\Inventory\Models\Product;

final class PriceListItem extends BaseModel
{
    use HasFactory;

    protected $table = 'sales_price_list_items';

    protected $fillable = [
        'price_list_id',
        'product_id',
        'price',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'min_quantity' => 'decimal:6',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
