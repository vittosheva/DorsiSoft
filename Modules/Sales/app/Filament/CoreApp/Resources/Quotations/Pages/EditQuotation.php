<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\InteractsWithQuotationHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\QuotationResource;

final class EditQuotation extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use InteractsWithQuotationHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use SyncsDocumentItemsCount;

    protected static string $resource = QuotationResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'quotation-items:persist';
    }

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [
                ...$this->getQuotationDecisionActions(),
            ]),
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getQuotationDuplicateAction(),
                deleteAction: DeleteAction::make(),
            ),
        );
    }
}
