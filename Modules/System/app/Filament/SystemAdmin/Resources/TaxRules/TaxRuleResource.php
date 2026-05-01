<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxRules;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Traits\HasActiveIcon;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages\CreateTaxRule;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages\EditTaxRule;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Pages\ListTaxRules;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\RelationManagers\TaxRuleLinesRelationManager;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Schemas\TaxRuleForm;
use Modules\System\Filament\SystemAdmin\Resources\TaxRules\Tables\TaxRulesTable;
use Modules\System\Models\TaxRule;
use UnitEnum;

final class TaxRuleResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = TaxRule::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 96;

    public static function form(Schema $schema): Schema
    {
        return TaxRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TaxRuleLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxRules::route('/'),
            'create' => CreateTaxRule::route('/create'),
            'edit' => EditTaxRule::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Application rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Application rules');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Tax Configuration');
    }
}
