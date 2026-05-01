<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Schemas\TaxCatalogForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\TaxCatalogResource;

final class CreateTaxCatalog extends BaseCreateRecord
{
    protected static string $resource = TaxCatalogResource::class;

    public function form(Schema $schema): Schema
    {
        return TaxCatalogForm::configure($schema);
    }
}
