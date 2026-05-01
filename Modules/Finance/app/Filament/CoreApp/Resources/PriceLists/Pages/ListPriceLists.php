<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;

final class ListPriceLists extends BaseListRecords
{
    protected static string $resource = PriceListResource::class;
}
