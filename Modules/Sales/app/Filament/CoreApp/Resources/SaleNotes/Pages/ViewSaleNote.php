<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Sales\Filament\Concerns\InteractsWithSaleNoteHeaderActions;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\SaleNoteResource;

final class ViewSaleNote extends ViewRecord
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
