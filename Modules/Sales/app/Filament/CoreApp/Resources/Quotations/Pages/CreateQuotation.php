<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages;

use Livewire\Component;
use Modules\Core\Support\Actions\ExtractDocumentFromFileAction;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\QuotationResource;

final class CreateQuotation extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExtractDocumentFromFileAction::make('extract_from_file')
                ->documentType('quotation')
                ->insertItemSuggestionsUsing(function (array $items, Component $livewire): void {
                    $livewire->dispatch('quotation-items:load-from-extraction', items: $items);
                }),
            ...parent::getHeaderActions(),
        ];
    }

    protected function getItemsPersistEvent(): string
    {
        return 'quotation-items:persist';
    }
}
