<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements\Pages\ListInventoryMovements;
use Modules\Inventory\Filament\CoreApp\Resources\InventoryMovements\Tables\InventoryMovementsTable;
use Modules\Inventory\Models\InventoryMovement;
use UnitEnum;

final class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $recordTitleAttribute = 'reference_code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'warehouse:id,code,name',
                'product:id,code,name',
                'documentType:id,code,name,movement_type',
                'lot:id,code',
                'creator:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return InventoryMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryMovements::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Inventory Movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Inventory Movements');
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
