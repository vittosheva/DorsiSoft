<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Models\Product;

final class DeliveryGuideItem extends Model
{
    protected $table = 'sales_delivery_guide_items';

    protected $fillable = [
        'delivery_guide_recipient_id',
        'product_id',
        'product_code',
        'product_name',
        'quantity',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'sort_order' => 'integer',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DeliveryGuideRecipient::class, 'delivery_guide_recipient_id');
    }

    public function product(): BelongsTo
    {
        return $this
            ->belongsTo(Product::class)
            ->whereIn('type', [ProductTypeEnum::Product, ProductTypeEnum::Kit]);
    }
}
