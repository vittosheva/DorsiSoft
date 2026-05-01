<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly float $requested,
        public readonly float $available,
    ) {
        parent::__construct(
            "Insufficient stock for product #{$productId} in warehouse #{$warehouseId}. "
            ."Requested: {$requested}, Available: {$available}"
        );
    }
}
