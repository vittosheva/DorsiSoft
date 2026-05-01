<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Traits\HasActiveIcon;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages\CreateTaxDefinition;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages\EditTaxDefinition;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Pages\ListTaxDefinitions;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\RelationManagers\TaxWithholdingRatesRelationManager;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Schemas\TaxDefinitionForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\Tables\TaxDefinitionsTable;
use Modules\System\Models\TaxDefinition;
use UnitEnum;

final class TaxDefinitionResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = TaxDefinition::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 96;

    public static function form(Schema $schema): Schema
    {
        return TaxDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxDefinitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TaxWithholdingRatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxDefinitions::route('/'),
            'create' => CreateTaxDefinition::route('/create'),
            'edit' => EditTaxDefinition::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Tax rate');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tax rates');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tax Configuration');
    }
}
