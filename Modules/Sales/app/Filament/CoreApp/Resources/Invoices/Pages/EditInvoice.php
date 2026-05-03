<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages;

use Filament\Actions\DeleteAction;
use Modules\Core\Support\Pages\BaseEditRecord;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\InteractsWithInvoiceHeaderActions;
use Modules\Sales\Filament\Concerns\InteractsWithSalesDocumentHeaderActions;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\Concerns\SyncsSequentialNumberEvent;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Models\Invoice;

final class EditInvoice extends BaseEditRecord
{
    use DispatchesItemsPersistEvent;
    use InteractsWithInvoiceHeaderActions;
    use InteractsWithSalesDocumentHeaderActions;
    use SyncsDocumentItemsCount;
    use SyncsSequentialNumberEvent;

    protected static string $resource = InvoiceResource::class;

    protected function getItemsPersistEvent(): string
    {
        return 'invoice-items:persist';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Invoice $record */
        $record = $this->getRecord();

        if (! $record->canEditRejectedElectronicDocumentInPlace()) {
            return $data;
        }

        return array_merge($data, $record->getInPlaceRejectedElectronicCorrectionAttributes());
    }

    protected function getHeaderActions(): array
    {
        /** @var Invoice $record */
        $record = $this->getRecord();

        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $this->getSalesDocumentPrimaryActions(extraActions: [
                $this->getInvoiceMarkPaidAction(),
            ]),
            electronicActions: [
                // IssueCreditNoteFromInvoiceAction::make(),
                $this->getInvoiceIssueAction(),
                ...$this->getSalesDocumentElectronicActions(InvoiceResource::class),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                duplicateAction: $this->getInvoiceDuplicateAction('duplicate'),
                deleteAction: DeleteAction::make()
                    ->visible(fn () => $record->status === InvoiceStatusEnum::Draft),
            ),
        );
    }
}
