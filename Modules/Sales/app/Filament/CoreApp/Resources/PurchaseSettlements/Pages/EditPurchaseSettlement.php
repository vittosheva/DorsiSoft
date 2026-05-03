<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages;

use Filament\Schemas\Schema;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\InteractsWithPurchaseSettlementHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Schemas\PurchaseSettlementForm;
use Modules\Sales\Models\PurchaseSettlement;

final class EditPurchaseSettlement extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use InteractsWithPurchaseSettlementHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use SyncsDocumentItemsCount;
    use SyncsSequentialNumberEvent;

    protected static string $resource = PurchaseSettlementResource::class;

    public function form(Schema $schema): Schema
    {
        return PurchaseSettlementForm::configure($schema);
    }

    protected function getItemsPersistEvent(): string
    {
        return 'purchase-settlement-items:persist';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PurchaseSettlement $record */
        $record = $this->getRecord();

        if (! $record->canEditRejectedElectronicDocumentInPlace()) {
            return $data;
        }

        return array_merge($data, $record->getInPlaceRejectedElectronicCorrectionAttributes());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return PurchaseSettlementForm::normalizeFormDataForFill($data);
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(),
            electronicActions: [
                $this->getPurchaseSettlementIssueAction(),
                ...$this->getSalesDocumentElectronicActions(PurchaseSettlementResource::class),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getPurchaseSettlementDuplicateAction(),
            ),
        );
    }
}
