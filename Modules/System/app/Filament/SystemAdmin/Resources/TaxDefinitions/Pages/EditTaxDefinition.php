<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\TaxDefinitionResource;

final class EditTaxDefinition extends BaseEditRecord
{
    protected static string $resource = TaxDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
