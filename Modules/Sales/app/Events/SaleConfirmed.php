<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SaleConfirmed
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public int $companyId,
        public int $saleId,
        public array $items,
    ) {}
}
