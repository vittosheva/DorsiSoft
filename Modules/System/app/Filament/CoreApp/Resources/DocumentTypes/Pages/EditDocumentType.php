<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\DocumentTypeResource;

final class EditDocumentType extends BaseEditRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DeleteAction::make(),
        ];
    }
}
