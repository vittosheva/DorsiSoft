<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\TaxApplications\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Finance\Enums\TaxTypeEnum;

final class TaxApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Automatic and immutable record of every tax applied in issued documents. Generated at the moment a document is issued and cannot be modified. For audit and compliance purposes only.'))
            ->columns([
                TextColumn::make('applicable_type')
                    ->label(__('Document Type'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->sortable(),

                TextColumn::make('applicable_id')
                    ->label(__('Document'))
                    ->sortable(),

                TextColumn::make('tax_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sri_code')
                    ->sortable(),

                TextColumn::make('base_amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('rate')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('tax_amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('applied_at')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tax_type')
                    ->options(TaxTypeEnum::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
