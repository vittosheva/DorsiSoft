<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\SriCatalogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Traits\HasActiveIcon;
use Modules\System\Filament\SystemAdmin\Resources\SriCatalogs\Pages\ListSriCatalogs;
use Modules\System\Filament\SystemAdmin\Resources\SriCatalogs\Tables\SriCatalogsTable;
use Modules\System\Models\SriCatalog;
use UnitEnum;

final class SriCatalogResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = SriCatalog::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?int $navigationSort = 98;

    public static function table(Table $table): Table
    {
        return SriCatalogsTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSriCatalogs::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('ATS Catalog');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ATS Catalogs');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tax Configuration');
    }
}
