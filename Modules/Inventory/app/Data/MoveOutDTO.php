<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Illuminate\Support\Carbon;

final readonly class MoveOutDTO
{
    public function __construct(
        public int $companyId,
        public int $warehouseId,
        public int $productId,
        public int $documentTypeId,
        public float $quantity,
        public Carbon $movementDate,
        public ?int $lotId = null,
        public ?int $serialId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $referenceCode = null,
        public ?string $notes = null,
    ) {}
}
