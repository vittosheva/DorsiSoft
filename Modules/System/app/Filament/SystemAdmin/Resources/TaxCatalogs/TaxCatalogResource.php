<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Traits\HasActiveIcon;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages\CreateTaxCatalog;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages\EditTaxCatalog;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Pages\ListTaxCatalogs;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\RelationManagers\TaxDefinitionsRelationManager;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Schemas\TaxCatalogForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Tables\TaxCatalogsTable;
use Modules\System\Models\TaxCatalog;
use UnitEnum;

final class TaxCatalogResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = TaxCatalog::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return TaxCatalogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxCatalogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TaxDefinitionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxCatalogs::route('/'),
            'create' => CreateTaxCatalog::route('/create'),
            'edit' => EditTaxCatalog::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Tax');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Taxes');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tax Configuration');
    }
}
