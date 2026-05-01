<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\Finance\Events\CollectionVoided;
use Modules\Finance\Listeners\CreateCollectionAllocationReversalsOnCollectionVoided;
use Modules\Sales\Events\CreditNoteIssued;
use Modules\Sales\Events\DeliveryGuideIssued;
use Modules\Sales\Events\InvoiceIssued;
use Modules\Sales\Events\InvoiceVoided;
use Modules\Sales\Events\SaleConfirmed;
use Modules\Sales\Listeners\ReserveStockOnSaleConfirmed;
use Modules\Sales\Listeners\UpdateOrderBillingStatusOnInvoiceChange;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        CreditNoteIssued::class => [],
        DeliveryGuideIssued::class => [],
        SaleConfirmed::class => [
            ReserveStockOnSaleConfirmed::class,
        ],
        InvoiceIssued::class => [
            UpdateOrderBillingStatusOnInvoiceChange::class,
        ],
        InvoiceVoided::class => [
            UpdateOrderBillingStatusOnInvoiceChange::class,
        ],
        CollectionVoided::class => [
            CreateCollectionAllocationReversalsOnCollectionVoided::class,
        ],
    ];
}
