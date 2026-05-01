<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\System\Enums\TaxGroupEnum;
use Modules\System\Enums\TaxNatureEnum;

final class TaxDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Tax type variants defined by the SRI, such as VAT 15%, VAT 5%, ICE on alcohol, and progressive income tax. Each definition specifies the applicable rate and official SRI codes. Tax definitions are global and shared across all companies.'))
            ->columns([
                CodeTextColumn::make('code'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tax_group')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('tax_type')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('rate')
                    ->formatStateUsing(fn (mixed $state): string => $state !== null
                        ? number_format((float) $state, 2).' %'
                        : '—')
                    ->alignment(Alignment::Right),

                TextColumn::make('sri_code')
                    ->alignment(Alignment::Center),

                TextColumn::make('sri_percentage_code')
                    ->label(__('Pct. Code'))
                    ->alignment(Alignment::Center),

                IconColumn::make('is_withholding')
                    ->boolean()
                    ->alignment(Alignment::Center),

                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),

                TextColumn::make('valid_to')
                    ->date()
                    ->placeholder('—'),

                IsActiveColumn::make('is_active'),
            ])
            ->filters([
                SelectFilter::make('tax_group')
                    ->options(TaxGroupEnum::class)
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tax_type')
                    ->options(TaxNatureEnum::class)
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('tax_group');
    }
}
