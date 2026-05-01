<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Sales\Models\SalesOrder;
use RuntimeException;

final class OrderAlreadyInvoicedException extends RuntimeException
{
    public function __construct(SalesOrder $order)
    {
        parent::__construct(
            "Order [{$order->code}] already has an invoice and cannot be invoiced again."
        );
    }
}
