<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Lots;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages\CreateLot;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages\EditLot;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\Pages\ListLots;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\Schemas\LotForm;
use Modules\Inventory\Filament\CoreApp\Resources\Lots\Tables\LotsTable;
use Modules\Inventory\Models\Lot;
use UnitEnum;

final class LotResource extends Resource
{
    protected static ?string $model = Lot::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product:id,code,name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return LotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLots::route('/'),
            // 'create' => CreateLot::route('/create'),
            // 'edit' => EditLot::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Lot');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Lots');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }
}
