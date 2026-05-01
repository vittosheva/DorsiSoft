<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Interfaces\Contracts\InvoicePoster;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\SalesOrder;

final class SalesInvoicePoster implements InvoicePoster
{
    public function __construct(private readonly SalesOrderToInvoiceConverter $converter) {}

    public function postInvoice(array $payload): void
    {
        $orderId = $payload['sale_id'] ?? null;

        if (! $orderId) {
            Log::warning('SalesInvoicePoster: missing sale_id in payload.');

            return;
        }

        // withoutGlobalScopes() is required because PostInvoiceOnSaleConfirmed is queued —
        // the job runs outside the HTTP request, so TenantScope would filter the record out.
        $order = SalesOrder::withoutGlobalScopes()->find($orderId);

        if (! $order) {
            Log::warning('SalesInvoicePoster: SalesOrder not found.', ['sale_id' => $orderId]);

            return;
        }

        // Idempotency guard — do not create a duplicate invoice
        if (Invoice::where('sales_order_id', $order->getKey())->exists()) {
            return;
        }

        $this->converter->convert($order);
    }
}
