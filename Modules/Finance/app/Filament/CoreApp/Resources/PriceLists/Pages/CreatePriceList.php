<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;

final class CreatePriceList extends BaseCreateRecord
{
    protected static string $resource = PriceListResource::class;
}
