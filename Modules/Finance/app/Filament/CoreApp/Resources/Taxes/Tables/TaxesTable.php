<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Columns\IsDefaultIconColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;
use Modules\Finance\Enums\TaxTypeEnum;

final class TaxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Configured taxes for this company. These are available for use in invoices, credit notes and other fiscal documents. Each company can manage its own tax configuration independently.'))
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('sri_code')
                    ->alignment(Alignment::Center),

                TextColumn::make('sri_percentage_code')
                    ->label(__('Pct. Code'))
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('rate')
                    ->formatStateUsing(fn (mixed $state, mixed $record): string => ($record->calculation_type?->value ?? $record->calculation_type) === 'fixed'
                        ? '$ '.number_format((float) $state, 2)
                        : number_format((float) $state, 2).' %')
                    ->sortable()
                    ->alignment(Alignment::Right),

                TextColumn::make('calculation_type')
                    ->badge()
                    ->alignment(Alignment::Center),

                IsDefaultIconColumn::make('is_default'),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TaxTypeEnum::class)
                    ->searchable()
                    ->preload(),
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
