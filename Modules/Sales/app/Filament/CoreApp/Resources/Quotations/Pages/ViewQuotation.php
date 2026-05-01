<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages;

use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithQuotationHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\QuotationResource;

final class ViewQuotation extends BaseViewRecord
{
    use InteractsWithQuotationHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;

    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [
                ...$this->getQuotationDecisionActions(),
            ]),
            managementActions: $this->getSalesDocumentManagementActions(
                duplicateAction: $this->getQuotationDuplicateAction(),
            ),
        );
    }
}
