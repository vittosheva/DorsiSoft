<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Core\Support\Forms\TextInputs\QuantityTextInput;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Models\PriceListItem;
use Modules\Inventory\Support\Forms\Selects\ProductSelect;

final class PriceListItemsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'items';

    public function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withAggregate('product', 'name')
            ->with([
                'priceList:id,currency_code',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProductSelect::make()
                    ->onlyForSale()
                    ->required()
                    ->columnSpanFull(),

                MoneyTextInput::make('price')
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD')
                    ->minValue(0)
                    ->required(),

                QuantityTextInput::make('min_quantity')
                    ->minValue(0.000001)
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Products with specific prices assigned to this price list. Each item overrides the product\'s default price when this price list is applied during invoice or quotation creation. Items can be added, updated, or removed as pricing agreements change over time.'))
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.code')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->searchable(),

                TextColumn::make('min_quantity')
                    ->numeric(decimalPlaces: 2),

                MoneyTextColumn::make('price')
                    ->currencyCode(fn (PriceListItem $record): string => $record->priceList->currency_code ?? 'USD'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
