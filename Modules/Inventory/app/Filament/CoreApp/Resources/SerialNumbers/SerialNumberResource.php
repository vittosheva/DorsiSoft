<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\SerialStatusEnum;
use Modules\Inventory\Filament\CoreApp\Resources\SerialNumbers\Pages\ListSerialNumbers;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Models\Warehouse;
use UnitEnum;

final class SerialNumberResource extends Resource
{
    protected static ?string $model = SerialNumber::class;

    protected static ?string $recordTitleAttribute = 'serial_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product:id,code,name',
                'warehouse:id,code,name',
                'lot:id,code',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_number')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('product.code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('warehouse.name')
                    ->placeholder('—'),

                TextColumn::make('lot.code')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('sold_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->options(fn () => Product::query()->active()->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('warehouse_id')
                    ->options(fn () => Warehouse::query()->active()->pluck('name', 'id')),

                SelectFilter::make('status')
                    ->options(SerialStatusEnum::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSerialNumbers::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Serial number');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Serial numbers');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
