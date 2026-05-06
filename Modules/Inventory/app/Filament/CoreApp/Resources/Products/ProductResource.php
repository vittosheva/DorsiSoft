<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Pages\CreateProduct;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Pages\EditProduct;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Pages\ListProducts;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Schemas\ProductForm;
use Modules\Inventory\Filament\CoreApp\Resources\Products\Tables\ProductsTable;
use Modules\Inventory\Models\Product;
use UnitEnum;

final class ProductResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'category:id,name',
                'brand:id,name',
                'unit:id,name,symbol',
                'tax:id,name,type,rate,calculation_type,is_active',
                'taxes:id,name,type,rate,calculation_type',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }
}
