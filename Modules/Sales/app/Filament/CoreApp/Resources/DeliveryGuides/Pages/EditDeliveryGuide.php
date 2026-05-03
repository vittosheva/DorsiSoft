<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sales\Filament\Concerns\InteractsWithDeliveryGuideHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;

final class EditDeliveryGuide extends BaseEditRecord
{
    use InteractsWithDeliveryGuideHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use SyncsSequentialNumberEvent;

    protected static string $resource = DeliveryGuideResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getSalesElectronicDocumentEditHeaderActions(
            DeliveryGuideResource::class,
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getDeliveryGuideIssueAction(),
            ],
            duplicateAction: $this->getDeliveryGuideDuplicateAction('duplicate'),
            deleteAction: DeleteAction::make()
                ->visible(fn () => $this->getRecord()->status === DeliveryGuideStatusEnum::Draft),
        );
    }
}
