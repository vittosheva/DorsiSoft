<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\TaxApplications\Tables;

use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Enums\TaxTypeEnum;

final class TaxApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Automatic and immutable record of every tax applied in issued documents. Generated at the moment a document is issued and cannot be modified. For audit and compliance purposes only.'))
            ->columns([
                TextColumn::make('applicable.code')
                    ->label(__('Document'))
                    ->description(
                        fn ($record) => str($record->applicable->establishment_code)
                            ->append('-')
                            ->append($record->applicable->emission_point_code)
                            ->append('-')
                            ->append($record->applicable->sequential_number)
                            ->toString()
                    )
                    ->sortable(),

                TextColumn::make('tax_type')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('sri_code')
                    ->alignment(Alignment::Center)
                    ->sortable(),

                MoneyTextColumn::make('base_amount')
                    ->money(),

                TextColumn::make('rate')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->alignment(Alignment::Right)
                    ->sortable(),

                MoneyTextColumn::make('tax_amount')
                    ->money(),

                TextColumn::make('applied_at')
                    ->date('d/m/Y')
                    ->alignment(Alignment::Center)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tax_type')
                    ->options(TaxTypeEnum::class),
            ])
            ->recordActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
