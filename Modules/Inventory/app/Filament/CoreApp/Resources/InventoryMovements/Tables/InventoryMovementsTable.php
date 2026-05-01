<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Inventory\Models\InventoryDocumentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;

final class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movement_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('documentType.name')
                    ->badge()
                    ->color(fn ($record) => $record->documentType?->movement_type?->getColor()),

                TextColumn::make('reference_code')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),

                TextColumn::make('warehouse.name'),

                TextColumn::make('lot.code')
                    ->placeholder('—'),

                TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 2)
                    ->alignment(Alignment::Right)
                    ->sortable(),

                TextColumn::make('unit_cost')
                    ->money()
                    ->alignment(Alignment::Right)
                    ->sortable(),

                TextColumn::make('voided_at')
                    ->label(__('Status'))
                    ->formatStateUsing(fn ($state) => $state ? __('Voided') : __('Active'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success'),

                CreatedByTextColumn::make(),
                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->options(fn () => Warehouse::query()->active()->pluck('name', 'id')),

                SelectFilter::make('document_type_id')
                    ->options(fn () => InventoryDocumentType::query()->active()->pluck('name', 'id')),

                SelectFilter::make('lot_id')
                    ->options(fn () => Lot::query()->active()->pluck('code', 'id'))
                    ->columnSpan(2),

                SelectFilter::make('voided')
                    ->label(__('Status'))
                    ->options([
                        'active' => __('Active'),
                        'voided' => __('Voided'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'active') {
                            return $query->whereNull('voided_at');
                        }

                        if ($data['value'] === 'voided') {
                            return $query->whereNotNull('voided_at');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                Action::make('void')
                    ->tooltip(__('Void Movement'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (InventoryMovement $record) => ! $record->isVoided() && ! $record->is_reversal)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('void_reason')
                            ->label(__('Reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (InventoryMovement $record, array $data): void {
                        app(InventoryService::class)->voidMovement(
                            $record,
                            $data['void_reason'],
                            filament()->auth()->id(),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
