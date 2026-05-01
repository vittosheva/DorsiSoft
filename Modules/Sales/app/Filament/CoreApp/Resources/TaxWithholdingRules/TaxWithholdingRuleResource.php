<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages\CreateTaxWithholdingRule;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages\EditTaxWithholdingRule;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Pages\ListTaxWithholdingRules;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Schemas\TaxWithholdingRuleForm;
use Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Tables\TaxWithholdingRulesTable;
use Modules\Sales\Models\TaxWithholdingRule;
use UnitEnum;

final class TaxWithholdingRuleResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = TaxWithholdingRule::class;

    protected static ?string $recordTitleAttribute = 'concept';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 71;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['company:id,name', 'creator:id,name']);
    }

    public static function form(Schema $schema): Schema
    {
        return TaxWithholdingRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxWithholdingRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxWithholdingRules::route('/'),
            'create' => CreateTaxWithholdingRule::route('/create'),
            'edit' => EditTaxWithholdingRule::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Tax withholding rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tax withholding rules');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
