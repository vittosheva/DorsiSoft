<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;

final class TaxCatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Tax categories defined by the SRI, such as VAT, ICE, income tax, and ISD. This is a global system catalog and does not vary per company.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tax_group')
                    ->badge()
                    ->sortable(),

                TextColumn::make('definitions_count')
                    ->counts('definitions')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->sortable(),

                ToggleColumn::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
