<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\SalesOrder;

final class UpdateOrderBillingStatusOnInvoiceChange
{
    public function handle(object $event): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->invoice;

        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::withoutGlobalScopes()->find($invoice->sales_order_id);

        if (! $order) {
            return;
        }

        if (! in_array($order->status, [
            SalesOrderStatusEnum::Confirmed,
            SalesOrderStatusEnum::PartiallyInvoiced,
            SalesOrderStatusEnum::FullyInvoiced,
        ], true)) {
            return;
        }

        $invoicedTotal = Invoice::where('sales_order_id', $order->getKey())
            ->whereIn('status', [InvoiceStatusEnum::Issued, InvoiceStatusEnum::Paid])
            ->sum('total');

        $newStatus = match (true) {
            $invoicedTotal <= 0 => SalesOrderStatusEnum::Confirmed,
            $invoicedTotal >= (float) $order->total => SalesOrderStatusEnum::FullyInvoiced,
            default => SalesOrderStatusEnum::PartiallyInvoiced,
        };

        if ($order->status !== $newStatus) {
            $order->status = $newStatus;
            $order->saveQuietly();
        }
    }
}
