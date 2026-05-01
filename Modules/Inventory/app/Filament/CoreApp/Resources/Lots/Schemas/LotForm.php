<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Inventory\Support\Forms\Selects\ProductSelect;

final class LotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Lot Information'))
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                ProductSelect::make()
                                    ->onlyTracksLots()
                                    ->required()
                                    ->columnSpan(6),

                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(3),

                                TextInput::make('supplier_lot_code')
                                    ->maxLength(100)
                                    ->columnSpan(3),

                                DatePicker::make('manufactured_date')
                                    ->required()
                                    ->columnSpan(3),

                                DatePicker::make('expiry_date')
                                    ->required()
                                    ->columnSpan(3),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
