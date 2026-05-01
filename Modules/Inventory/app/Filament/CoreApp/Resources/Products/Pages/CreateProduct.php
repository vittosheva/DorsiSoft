<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Products\ProductResource;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Schemas\ProductForm;

final class CreateProduct extends BaseCreateRecord
{
    protected static string $resource = ProductResource::class;

    public function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }
}
