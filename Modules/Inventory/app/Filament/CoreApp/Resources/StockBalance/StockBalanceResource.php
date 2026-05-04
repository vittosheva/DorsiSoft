<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\StockBalance;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Inventory\Filament\CoreApp\Resources\StockBalance\Pages\ListStockBalances;
use Modules\Inventory\Models\InventoryBalance;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Warehouse;
use UnitEnum;

final class StockBalanceResource extends Resource
{
    protected static ?string $model = InventoryBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product:id,code,name,reorder_point',
                'warehouse:id,code,name',
                'lot:id,code',
            ])
            ->join('inv_products', 'inv_products.id', '=', 'inv_balances.product_id')
            ->select('inv_balances.*');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.code')
                    ->label(__('SKU'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('warehouse.name'),

                TextColumn::make('lot.code')
                    ->placeholder('—'),

                TextColumn::make('quantity_available')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn (InventoryBalance $record): string => $record->product?->reorder_point !== null
                        && $record->quantity_available <= $record->product->reorder_point
                        ? 'danger'
                        : 'success')
                    ->alignment(Alignment::Right),

                TextColumn::make('quantity_reserved')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color('warning')
                    ->alignment(Alignment::Right),

                TextColumn::make('product.reorder_point')
                    ->label(__('Reorder Point'))
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—')
                    ->alignment(Alignment::Center),

                MoneyTextColumn::make('average_cost')
                    ->numeric(decimalPlaces: 2)
                    ->withoutDefaultSummarizer()
                    ->money(),

                MoneyTextColumn::make('balance_value')
                    ->label(__('Value'))
                    ->numeric(decimalPlaces: 2)
                    ->money()
                    ->withoutDefaultSummarizer()
                    ->summarize(
                        Summarizer::make()
                            ->label(__('Total Value'))
                            ->using(fn (\Illuminate\Database\Query\Builder $query): float => (float) $query->selectRaw('COALESCE(SUM(quantity_available * average_cost), 0)')->value('coalesce'))
                            ->money()
                    )
                    ->state(fn (InventoryBalance $record): float => round((float) $record->quantity_available * (float) $record->average_cost, 2)),

                TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->options(fn () => Warehouse::query()->active()->pluck('name', 'id'))
                    ->columnSpan(3),

                SelectFilter::make('lot_id')
                    ->options(fn () => Lot::query()->active()->pluck('code', 'id'))
                    ->columnSpan(2),

                Filter::make('below_reorder_point')
                    ->query(fn (Builder $query) => $query->belowReorderPoint())
                    ->columnSpan(2),

                Filter::make('has_stock')
                    ->query(fn (Builder $query) => $query->where('quantity_available', '>', 0))
                    ->columnSpan(2),
            ])
            ->defaultSort('inv_products.created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockBalances::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Stock');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
