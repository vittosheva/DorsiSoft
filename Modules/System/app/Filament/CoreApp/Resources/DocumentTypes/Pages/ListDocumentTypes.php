<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Filament\Actions\CreateAction;
use Modules\Core\Support\Pages\BaseListRecords;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\DocumentTypeResource;

final class ListDocumentTypes extends BaseListRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
