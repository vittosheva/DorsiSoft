<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Illuminate\Support\Carbon;

final readonly class TransferDTO
{
    public function __construct(
        public int $companyId,
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public int $productId,
        public int $documentTypeId,
        public float $quantity,
        public Carbon $movementDate,
        public ?int $lotId = null,
        public ?int $serialId = null,
        public ?string $referenceCode = null,
        public ?string $notes = null,
    ) {}
}
