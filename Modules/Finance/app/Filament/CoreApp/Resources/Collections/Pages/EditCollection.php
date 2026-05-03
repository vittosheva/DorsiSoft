<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Finance\Filament\Concerns\InteractsWithCollectionHeaderActions;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;

final class EditCollection extends BaseEditRecord
{
    use InteractsWithCollectionHeaderActions;

    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCollectionVoidAction(),

            $this->getCollectionDuplicateAction('duplicate'),

            DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->isVoided() && $this->getRecord()->allocations()->doesntExist()),
        ];
    }
}
