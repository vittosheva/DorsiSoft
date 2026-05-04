<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Filters\CreatorFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;

final class BusinessPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Business partners registered for this company, including customers, suppliers, and carriers. Partners are used across all commercial and fiscal documents such as invoices, purchase settlements, withholdings, and delivery guides. Each company maintains its own independent partner directory.'))
            ->columns([
                CodeTextColumn::make('code'),
                TextColumn::make('legal_name')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $innerQuery) use ($search): void {
                            $searchPattern = '%'.$search.'%';

                            $innerQuery
                                ->whereLike('legal_name', $searchPattern)
                                ->orWhereLike('trade_name', $searchPattern);

                            if (mb_strlen($search) >= 3) {
                                $innerQuery->orWhereFullText(['legal_name', 'trade_name'], $search);
                            }
                        }),
                    )
                    ->sortable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('identification_number')
                    ->searchable(),
                TextColumn::make('roles.code')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge(),
                TextColumn::make('email')
                    ->formatStateUsing(fn (mixed $state): string => is_array($state) ? implode(', ', $state) : ($state ?? ''))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('email', 'like', "{$search}%"),
                    ),
                TextColumn::make('phone')
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('phone', 'like', "{$search}%"),
                    ),
                TextColumn::make('customerDetail.seller.name')
                    ->searchable(isGlobal: false)
                    ->sortable(),
                IsActiveColumn::make('is_active'),
                CreatedByTextColumn::make(),
                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->select($query->qualifyColumns(['id', 'name']))
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                    )
                    ->indicator(__('Roles'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => __($record->name))
                    ->indicateUsing(function (SelectFilter $filter, array $state): array {
                        if (blank($state['values'] ?? null)) {
                            return [];
                        }

                        $labels = $filter
                            ->getRelationshipQuery()
                            ->whereKey($state['values'])
                            ->pluck('name')
                            ->map(static fn (string $label): string => __($label))
                            ->all();

                        if ($labels === []) {
                            return [];
                        }

                        return [Indicator::make(__('Roles').': '.collect($labels)->join(', ', ' & '))];
                    })
                    ->preload()
                    ->searchable()
                    ->multiple(),
                CreatorFilter::make('creator'),
                StatusFilter::make('is_active'),
                TrashedFilter::make('deleted_at'),
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
