<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages\CreatePriceList;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages\EditPriceList;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages\ListPriceLists;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Pages\ViewPriceList;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\RelationManagers\PriceListItemsRelationManager;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Schemas\PriceListForm;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\Tables\PriceListsTable;
use Modules\Finance\Models\PriceList;
use UnitEnum;

final class PriceListResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = PriceList::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PriceListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PriceListItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceLists::route('/'),
            'create' => CreatePriceList::route('/create'),
            'edit' => EditPriceList::route('/{record}/edit'),
            'view' => ViewPriceList::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Price List');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Price Lists');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Finance');
    }
}
