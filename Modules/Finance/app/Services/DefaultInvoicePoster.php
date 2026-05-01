<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Interfaces\Contracts\InvoicePoster;

final class DefaultInvoicePoster implements InvoicePoster
{
    public function postInvoice(array $payload): void
    {
        Log::info('Finance invoice posting requested.', [
            'company_id' => $payload['company_id'] ?? null,
            'sale_id' => $payload['sale_id'] ?? null,
            'source' => $payload['source'] ?? null,
            'items_count' => isset($payload['items']) && is_array($payload['items']) ? count($payload['items']) : 0,
        ]);
    }
}
