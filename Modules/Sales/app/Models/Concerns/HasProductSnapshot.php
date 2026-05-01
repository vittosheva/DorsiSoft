<?php

declare(strict_types=1);

namespace Modules\Sales\Models\Concerns;

use Modules\Inventory\Models\Product;

/**
 * Populates denormalized product snapshot fields from a Product instance.
 *
 * Used by QuotationItem, InvoiceItem, and SalesOrderItem.
 * Tax snapshot is handled separately via the *ItemTax relational tables — not here.
 */
trait HasProductSnapshot
{
    public function populateProductSnapshot(Product $product): void
    {
        $this->product_id = $product->getKey();
        $this->product_code = $product->code;
        $this->product_name = $product->name;
        $this->product_unit = $product->unit?->symbol;
        $this->description = $product->name;
        $this->unit_price = $product->sale_price ?? 0;
    }
}
