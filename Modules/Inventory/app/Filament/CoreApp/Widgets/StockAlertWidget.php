<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Widgets;

use Filament\Facades\Filament;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Inventory\Models\InventoryBalance;

final class StockAlertWidget extends TableWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = null;

    public function getHeading(): string
    {
        return __('Stock Alerts — Below Reorder Point');
    }

    public function table(Table $table): Table
    {
        $companyId = Filament::getTenant()?->getKey();

        return $table
            ->query(
                InventoryBalance::query()
                    ->where('inv_balances.company_id', $companyId)
                    ->belowReorderPoint()
                    ->with(['product:id,code,name,reorder_point', 'warehouse:id,code,name'])
                    ->select('inv_balances.*')
            )
            ->columns([
                TextColumn::make('product.code')
                    ->label(__('SKU'))
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->searchable(),

                TextColumn::make('warehouse.name'),

                TextColumn::make('quantity_available')
                    ->label(__('Available'))
                    ->numeric(decimalPlaces: 2)
                    ->color('danger'),

                TextColumn::make('product.reorder_point')
                    ->label(__('Reorder Point'))
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('average_cost')
                    ->label(__('Avg. Cost'))
                    ->money(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
