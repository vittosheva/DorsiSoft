<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Columns\IsDefaultIconColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;

final class PriceListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Price lists configured for this company, each associated with a currency and an optional validity period. Price lists allow applying different pricing structures to different customers or commercial contexts. Products can have specific prices defined per price list.'))
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currency_code')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('start_date')
                    ->label(__('Valid From'))
                    ->date()
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label(__('Valid Until'))
                    ->date()
                    ->placeholder('—'),

                IsDefaultIconColumn::make('is_default'),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                DateRangeFilter::make('start_date')
                    ->label(__('Valid From')),
                IsActiveFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
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
