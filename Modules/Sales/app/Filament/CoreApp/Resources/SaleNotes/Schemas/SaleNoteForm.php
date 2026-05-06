<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Finance\Support\Forms\Selects\PriceListSelect;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Livewire\SaleNoteItems;

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
                                    ->dehydrated(false)
                                    ->columnSpan(3),
                                SellerUserSelect::make('seller_id')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->columnSpan(8),
                    ])
                    ->columnSpanFull(),

                Livewire::make(SaleNoteItems::class, [
                    'minimumItemsCount' => 1,
                    'minimumItemsValidationMessage' => __('At least one item is required'),
                ])
                    ->key('sale-note-items')
                    ->columnSpanFull(),

                Grid::make(12)
                    ->schema([
                        Section::make(__('Notes'))
                            ->columnSpan(6)
                            ->schema([
                                Textarea::make('notes')->rows(3)->columnSpanFull(),
                            ]),

                        Section::make(__('Audit'))
                            ->columnSpan(6)
                            ->schema([]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
