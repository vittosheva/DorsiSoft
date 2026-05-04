<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;

final class ViewPriceList extends BaseViewRecord
{
    protected static string $resource = PriceListResource::class;
}
