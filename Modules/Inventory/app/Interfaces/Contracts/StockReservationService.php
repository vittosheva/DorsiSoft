<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Contracts;

interface StockReservationService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function reserveForSale(int $companyId, array $items, string $reference): void;
}
