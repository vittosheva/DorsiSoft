<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\TaxCatalogResource;

final class ListTaxCatalogs extends BaseListRecords
{
    protected static string $resource = TaxCatalogResource::class;
}
