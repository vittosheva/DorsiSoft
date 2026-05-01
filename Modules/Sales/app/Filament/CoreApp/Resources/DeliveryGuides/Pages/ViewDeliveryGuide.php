<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithDeliveryGuideHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\DeliveryGuideResource;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditPanel;
use Modules\Sri\Support\Concerns\ShowsElectronicError;

final class ViewDeliveryGuide extends BaseViewRecord
{
    use InteractsWithDeliveryGuideHeaderActions;
    use InteractsWithElectronicAuditPanel;
    use InteractsWithSalesDocumentHeaderActions;
    use ShowsElectronicError;

    protected static string $resource = DeliveryGuideResource::class;

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgetData(): array
    {
        return $this->getElectronicAuditWidgetData();
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getDeliveryGuideIssueAction(),
                ...$this->getSalesDocumentElectronicActions(DeliveryGuideResource::class),
            ],
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getDeliveryGuideDuplicateAction(),
            ),
        );
    }

    /* protected function getHeaderActions2(): array
    {
        return $this->getSalesElectronicDocumentViewHeaderActions(
            DeliveryGuideResource::class,
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getDeliveryGuideIssueAction(),
            ],
            duplicateAction: $this->getDeliveryGuideDuplicateAction(),
        );
    } */
}
