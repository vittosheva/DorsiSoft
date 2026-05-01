<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\System\Enums\TaxAppliesToEnum;

final class TaxRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Conditions that determine when and to whom each tax rate applies, including taxpayer type, product category, and document type. Progressive income tax and ICE brackets are defined as ordered rule lines.'))
            ->columns([
                TextColumn::make('priority')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('applies_to')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('taxDefinition.name')
                    ->label(__('Tax Definition'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('conditions')
                    ->formatStateUsing(function (mixed $state): string {
                        if (empty($state)) {
                            return '—';
                        }

                        // If state is a string (JSON), decode it
                        if (is_string($state)) {
                            $state = json_decode($state, true);
                        }

                        if (! is_array($state)) {
                            return '—';
                        }

                        return collect($state)
                            ->map(function ($c): string {
                                if (is_array($c)) {
                                    return ($c['field'] ?? '').' '.($c['operator'] ?? '').' '.json_encode($c['value'] ?? null);
                                }

                                // fallback: just cast to string
                                return (string) $c;
                            })
                            ->implode(' AND ');
                    })
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),

                TextColumn::make('valid_to')
                    ->date()
                    ->placeholder('—'),

                IsActiveColumn::make('is_active'),
            ])
            ->filters([
                SelectFilter::make('applies_to')
                    ->options(TaxAppliesToEnum::class)
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
            ->defaultSort('priority');
    }
}
