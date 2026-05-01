<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Finance\Interfaces\Contracts\InvoicePoster;
use Modules\Sales\Events\SaleConfirmed;

final class PostInvoiceOnSaleConfirmed implements ShouldQueue
{
    public function __construct(private readonly InvoicePoster $invoicePoster) {}

    public function handle(SaleConfirmed $event): void
    {
        $this->invoicePoster->postInvoice([
            'company_id' => $event->companyId,
            'sale_id' => $event->saleId,
            'items' => $event->items,
            'source' => 'sales.sale_confirmed',
        ]);
    }
}
