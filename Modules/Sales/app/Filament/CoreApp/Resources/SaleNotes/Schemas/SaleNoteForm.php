<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Finance\Support\Forms\Selects\PriceListSelect;
use Modules\Inventory\Support\Forms\Selects\WarehouseSelect;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Livewire\SaleNoteItems;
use Modules\Sales\Models\SaleNote;

final class SaleNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make(__('Sale Note Data'))
                            ->schema([
                                CodeTextInput::make('code'),
                                IssueDatePicker::make('issue_date'),
                            ])
                            ->columns(2)
                            ->columnSpan(4),

                        Section::make(__('Customer'))
                            ->schema([
                                CustomerBusinessPartnerSelect::make('business_partner_id')
                                    ->columnSpan(6),
                                PriceListSelect::make('price_list_id')
                                    ->columnSpan(3),
                                SellerUserSelect::make('seller_id')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->columnSpan(8),
                    ])
                    ->columnSpanFull(),

                WarehouseSelect::make('warehouse_id')
                    ->columnSpan(4),

                Livewire::make(SaleNoteItems::class, fn (?SaleNote $record, string $operation) => [
                    'saleNoteId' => $record?->getKey(),
                    'minimumItemsCount' => 1,
                    'minimumItemsValidationMessage' => __('At least one item is required'),
                    'operation' => $operation,
                ])
                    ->key('sale-note-items')
                    ->columnSpanFull(),

                TextInput::make('document_items_count')
                    ->hiddenLabel()
                    ->readOnly()
                    ->dehydrated(false)
                    ->rules(['integer', 'min:1'])
                    ->validationMessages(['min' => __('At least one item is required')])
                    ->extraInputAttributes(['class' => 'sr-only'])
                    ->extraAttributes(['class' => 'hidden has-[.fi-fo-field-wrp-error-message]:block']),

                TextInput::make('document_items_total')
                    ->hiddenLabel()
                    ->hidden()
                    ->readOnly()
                    ->dehydrated(false),

                Grid::make(12)
                    ->schema([
                        Section::make(__('Notes'))
                            ->columnSpan(6)
                            ->schema([
                                Textarea::make('notes')->rows(3)->columnSpanFull(),
                            ]),

                        AuditSection::make()
                            ->columnSpan(6),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
