<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;

final class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Units of measure used when defining products and their quantities in sales documents. Each unit includes a code, name, and symbol that appear on printed and electronic documents. Units of measure are shared across the company\'s product catalog.'))
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('symbol')
                    ->placeholder('—'),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                IsActiveFilter::make('is_active'),
            ])
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
