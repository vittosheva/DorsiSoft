<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Filament\Actions;
use Modules\Core\Support\Pages\BaseViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithSaleNoteHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class ViewSaleNote extends BaseViewRecord
{
    use InteractsWithSaleNoteHeaderActions;

    protected static string $resource = SaleNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaleNoteIssueAction(),
            $this->getSaleNoteVoidAction(),
            $this->getSaleNoteConvertToInvoiceAction(),
            $this->getSaleNoteGeneratePdfAction(),
            Actions\EditAction::make()->visible(fn () => $this->record->status->isEditable()),
            Actions\DeleteAction::make()->visible(fn () => $this->record->status->isEditable()),
        ];
    }
}
