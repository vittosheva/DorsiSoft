<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Illuminate\Support\Carbon;

final readonly class AdjustDTO
{
    public function __construct(
        public int $companyId,
        public int $warehouseId,
        public int $productId,
        public int $documentTypeId,
        /** Positive = entry adjustment, negative = exit adjustment */
        public float $quantityDelta,
        public float $unitCost,
        public Carbon $movementDate,
        public ?int $lotId = null,
        public ?string $referenceCode = null,
        public ?string $notes = null,
    ) {}
}
