<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages\CreateCategory;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages\EditCategory;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Pages\ListCategories;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Schemas\CategoryForm;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Tables\CategoriesTable;
use Modules\Inventory\Models\Category;
use UnitEnum;

final class CategoryResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Category::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'parent:id,name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            // 'create' => CreateCategory::route('/create'),
            // 'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }
}
