<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Schemas\TaxDefinitionForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\TaxDefinitionResource;

final class CreateTaxDefinition extends BaseCreateRecord
{
    protected static string $resource = TaxDefinitionResource::class;

    public function form(Schema $schema): Schema
    {
        return TaxDefinitionForm::configure($schema);
    }
}
