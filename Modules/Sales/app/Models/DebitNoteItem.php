<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Product;
use Modules\Sales\Enums\DiscountTypeEnum;
use Modules\Sales\Models\Concerns\HasProductSnapshot;

final class DebitNoteItem extends Model
{
    use HasProductSnapshot;

    protected $table = 'sales_debit_note_items';

    protected $fillable = [
        'debit_note_id',
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
        'subtotal',
        'tax_amount',
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

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class, 'debit_note_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(DebitNoteItemTax::class, 'debit_note_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
