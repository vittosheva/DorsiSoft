<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages\CreateBrand;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages\EditBrand;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Pages\ListBrands;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Schemas\BrandForm;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Tables\BrandsTable;
use Modules\Inventory\Models\Brand;
use UnitEnum;

final class BrandResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Brand::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static ?int $navigationSort = 70;

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
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            // 'create' => CreateBrand::route('/create'),
            // 'edit' => EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Brand');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Brands');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }
}
