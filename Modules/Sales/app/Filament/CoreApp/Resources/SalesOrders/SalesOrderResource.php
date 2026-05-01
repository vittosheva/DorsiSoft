<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SalesOrders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages\CreateSalesOrder;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages\ListSalesOrders;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Pages\ViewSalesOrder;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Schemas\SalesOrderForm;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\Tables\SalesOrdersTable;
use Modules\Sales\Models\SalesOrder;
use UnitEnum;

final class SalesOrderResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = SalesOrder::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'seller:id,name',
                'quotation:id,code',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SalesOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
            'view' => ViewSalesOrder::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Sale order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sales orders');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
