<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;

final class CreateBrand extends BaseCreateRecord
{
    protected static string $resource = BrandResource::class;
}
