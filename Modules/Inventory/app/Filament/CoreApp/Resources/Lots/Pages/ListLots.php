<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\LotResource;

final class ListLots extends BaseListRecords
{
    protected static string $resource = LotResource::class;
}
