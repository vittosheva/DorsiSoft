<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;

final class EstablishmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Establishments registered for this company, each representing a physical or virtual location recognized by the SRI. Establishments define the emission points from which electronic documents are issued. Every document must be associated with an active establishment.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => __('Emission Points').': '.$record->emissionPoints?->pluck('code')->implode(', '))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    // ->limit(60)
                    ->grow(false)
                    ->toggleable(),

                IsActiveColumn::make('is_active'),

                TextColumn::make('emission_points_count')
                    ->label(__('Emission Points'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
