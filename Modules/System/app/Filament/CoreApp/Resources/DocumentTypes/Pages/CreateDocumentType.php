<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\DocumentTypeResource;

final class CreateDocumentType extends BaseCreateRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
