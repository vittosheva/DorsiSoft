<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\CategoryResource;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Schemas\CategoryForm;

final class CreateCategory extends BaseCreateRecord
{
    protected static string $resource = CategoryResource::class;

    public function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }
}
