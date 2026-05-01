<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\DocumentTypeResource;

final class ListDocumentTypes extends ListRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
