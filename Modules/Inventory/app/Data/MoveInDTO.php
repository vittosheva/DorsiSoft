<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Illuminate\Support\Carbon;

final readonly class MoveInDTO
{
    public function __construct(
        public int $companyId,
        public int $warehouseId,
        public int $productId,
        public int $documentTypeId,
        public float $quantity,
        public float $unitCost,
        public Carbon $movementDate,
        public ?int $lotId = null,
        public array $serialNumbers = [],
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $referenceCode = null,
        public ?string $notes = null,
    ) {}
}
