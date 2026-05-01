<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\TaxDefinitionResource;

final class ListTaxDefinitions extends BaseListRecords
{
    protected static string $resource = TaxDefinitionResource::class;
}
