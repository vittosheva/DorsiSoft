<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;

final class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('establishment.name')
                    ->placeholder('—'),

                TextColumn::make('address')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('movements_count')
                    ->label(__('Movements'))
                    ->counts('movements')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->boolean()
                    ->alignment(Alignment::Center),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),
                CreatedAtTextColumn::make(),
            ])
            ->filters([
                IsActiveFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
