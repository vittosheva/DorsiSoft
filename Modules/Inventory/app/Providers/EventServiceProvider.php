<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Modules\Core\Events\CompanyCreated;
use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\Inventory\Events\InventoryMovementCreated;
use Modules\Inventory\Events\InventoryMovementVoided;
use Modules\Inventory\Events\StockBelowReorderPoint;
use Modules\Inventory\Listeners\CreateInventoryMovementOnInvoiceIssued;
use Modules\Inventory\Listeners\CreateInventoryMovementOnPurchaseSettlementIssued;
use Modules\Inventory\Listeners\ProvisionDefaultWarehouseOnCompanyCreated;
use Modules\Sales\Events\InvoiceIssued;
use Modules\Sales\Events\PurchaseSettlementIssued;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        InvoiceIssued::class => [
            CreateInventoryMovementOnInvoiceIssued::class,
        ],
        PurchaseSettlementIssued::class => [
            CreateInventoryMovementOnPurchaseSettlementIssued::class,
        ],
        CompanyCreated::class => [
            ProvisionDefaultWarehouseOnCompanyCreated::class,
        ],
        InventoryMovementCreated::class => [],
        InventoryMovementVoided::class => [],
        StockBelowReorderPoint::class => [],
    ];
}
