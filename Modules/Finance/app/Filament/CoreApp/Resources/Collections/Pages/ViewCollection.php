<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Finance\Filament\Concerns\InteractsWithCollectionHeaderActions;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;
use Modules\Workflow\Filament\CoreApp\Widgets\ApprovalHistoryWidget;

final class ViewCollection extends BaseViewRecord
{
    use InteractsWithCollectionHeaderActions;

    protected static string $resource = CollectionResource::class;

    protected function getFooterWidgets(): array
    {
        return [ApprovalHistoryWidget::class];
    }

    protected function getFooterWidgetData(): array
    {
        return ['record' => $this->getRecord()];
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getCollectionApprovalActions(),
            $this->getCollectionEditAction(),
        ];
    }
}
