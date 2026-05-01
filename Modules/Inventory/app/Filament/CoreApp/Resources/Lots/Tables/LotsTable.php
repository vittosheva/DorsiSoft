<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Inventory\Models\Product;

final class LotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('code')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('supplier_lot_code')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('manufactured_date')
                    ->date()
                    ->placeholder('—')
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->expiry_date && Carbon::parse($record->expiry_date)->isPast()
                        ? 'danger'
                        : ($record->expiry_date && Carbon::parse($record->expiry_date)->diffInDays(now()) <= 30
                            ? 'warning'
                            : null))
                    ->placeholder('—')
                    ->alignment(Alignment::Center),

                IsActiveColumn::make('is_active'),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->options(fn () => Product::query()->active()->pluck('name', 'id'))
                    ->searchable(),

                TernaryFilter::make('is_active'),

                Filter::make('expiring_soon')
                    ->label(__('Expiring in 30 days'))
                    ->query(fn (Builder $query) => $query->expiringSoon(30))
                    ->columnSpan(2),

                Filter::make('expired')
                    ->query(fn (Builder $query) => $query->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', today())),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
