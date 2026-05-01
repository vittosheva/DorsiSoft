<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\CategoryResource;

final class ListCategories extends BaseListRecords
{
    protected static string $resource = CategoryResource::class;
}
