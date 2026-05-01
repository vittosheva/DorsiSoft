<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\RelationManagers;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;

final class TaxDefinitionsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'definitions';

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Specific tax definitions belonging to this tax catalog type. Each definition represents a distinct rate variant such as VAT 15%, VAT 5%, or ICE on alcoholic beverages. Definitions include the official SRI rate codes and are shared across all companies.'))
            ->heading(__('Tax Definition'))
            ->recordTitleAttribute('name')
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('calculation_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('rate')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('valid_from')
                    ->date('d/m/Y'),

                TextColumn::make('valid_to')
                    ->date('d/m/Y'),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
