<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Units\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Units\UnitResource;

final class ListUnits extends BaseListRecords
{
    protected static string $resource = UnitResource::class;
}
