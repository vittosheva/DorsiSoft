<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Schemas\BrandForm;

final class CreateBrand extends BaseCreateRecord
{
    protected static string $resource = BrandResource::class;

    public function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }
}
