<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\SriCatalogs\Tables;

use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Sri\Enums\SriCatalogTypeEnum;

final class SriCatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Official SRI catalog codes used for ATS compliance, including document types, withholding codes, payment methods, countries, and other regulatory catalogs. This is a global system catalog.'))
            ->columns([
                TextColumn::make('catalog_type')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                CodeTextColumn::make('code')
                    ->copyable()
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::Center),

                TextColumn::make('name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('valid_from')
                    ->date()
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('valid_to')
                    ->date()
                    ->placeholder('—')
                    ->alignment(Alignment::Center),

                TextColumn::make('sort_order')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true),

                IsActiveColumn::make('is_active'),
            ])
            ->filters([
                SelectFilter::make('catalog_type')
                    ->options(SriCatalogTypeEnum::class)
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('catalog_type');
    }
}
