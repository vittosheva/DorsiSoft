<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Log;
use Modules\Inventory\Interfaces\Contracts\StockReservationService;

final class DefaultStockReservationService implements StockReservationService
{
    public function reserveForSale(int $companyId, array $items, string $reference): void
    {
        Log::info('Inventory stock reservation requested.', [
            'company_id' => $companyId,
            'reference' => $reference,
            'items_count' => count($items),
        ]);
    }
}
