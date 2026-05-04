<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Inventory\Enums\MovementTypeEnum;

final class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('movement_type')
                    ->badge(),

                IconColumn::make('affects_inventory')
                    ->boolean()
                    ->alignment(Alignment::Center),

                IconColumn::make('requires_source_document')
                    ->boolean()
                    ->alignment(Alignment::Center),

                IsActiveColumn::make('is_active'),

                TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(50),
            ])
            ->filters([
                SelectFilter::make('movement_type')
                    ->options(MovementTypeEnum::class)
                    ->searchable()
                    ->multiple(),
                TernaryFilter::make('affects_inventory'),
                TernaryFilter::make('requires_source_document'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
