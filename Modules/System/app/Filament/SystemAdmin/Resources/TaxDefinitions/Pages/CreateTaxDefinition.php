<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\TaxDefinitionResource;

final class CreateTaxDefinition extends BaseCreateRecord
{
    protected static string $resource = TaxDefinitionResource::class;
}
