<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages\CreateWarehouse;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages\EditWarehouse;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Pages\ListWarehouses;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Schemas\WarehouseForm;
use Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Tables\WarehousesTable;
use Modules\Inventory\Models\Warehouse;
use UnitEnum;

final class WarehouseResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Warehouse::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'establishment:id,name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            // 'create' => CreateWarehouse::route('/create'),
            // 'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Warehouse');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Warehouses');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }
}
