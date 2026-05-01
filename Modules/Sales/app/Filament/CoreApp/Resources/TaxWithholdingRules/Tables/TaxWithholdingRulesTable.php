<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;

final class TaxWithholdingRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type'),
                TextColumn::make('concept')->searchable(),
                TextColumn::make('percentage')->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', '')),
                CreatedByTextColumn::make(),
                CreatedAtTextColumn::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
