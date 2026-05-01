<?php

declare(strict_types=1);

namespace Modules\Finance\Interfaces\Contracts;

interface InvoicePoster
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function postInvoice(array $payload): void;
}
