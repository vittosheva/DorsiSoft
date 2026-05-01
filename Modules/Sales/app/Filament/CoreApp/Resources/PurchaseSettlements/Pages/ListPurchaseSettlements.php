<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;

final class ListPurchaseSettlements extends BaseListRecords
{
    protected static string $resource = PurchaseSettlementResource::class;
}
