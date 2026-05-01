<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages;

use Filament\Actions\CreateAction;
use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\DocumentSequenceResource;

final class ListDocumentSequences extends BaseListRecords
{
    protected static string $resource = DocumentSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('4xl')
                ->modalHeading(__('Create')),
        ];
    }
}
