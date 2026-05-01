<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\Product;

final class StockBelowReorderPoint
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $warehouseId,
        public readonly Product $product,
        public readonly float $currentQuantity,
    ) {}
}
