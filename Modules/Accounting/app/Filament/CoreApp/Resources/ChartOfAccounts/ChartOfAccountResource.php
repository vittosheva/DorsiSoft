<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Pages\CreateChartOfAccount;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Pages\EditChartOfAccount;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Pages\ListChartOfAccounts;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Schemas\ChartOfAccountForm;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Tables\ChartOfAccountsTable;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Core\Traits\HasActiveIcon;
use UnitEnum;

final class ChartOfAccountResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = ChartOfAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ChartOfAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChartOfAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChartOfAccounts::route('/'),
            // 'create' => CreateChartOfAccount::route('/create'),
            // 'edit' => EditChartOfAccount::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Chart of accounts');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Accounting');
    }
}
