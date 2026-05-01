<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Inventory\Interfaces\Contracts\StockReservationService;
use Modules\Sales\Events\SaleConfirmed;

final class ReserveStockOnSaleConfirmed implements ShouldQueue
{
    public function __construct(private readonly StockReservationService $stockReservationService) {}

    public function handle(SaleConfirmed $event): void
    {
        $this->stockReservationService->reserveForSale(
            companyId: $event->companyId,
            items: $event->items,
            reference: sprintf('sale:%d', $event->saleId),
        );
    }
}
