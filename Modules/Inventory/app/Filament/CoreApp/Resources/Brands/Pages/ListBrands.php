<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;

final class ListBrands extends BaseListRecords
{
    protected static string $resource = BrandResource::class;
}
