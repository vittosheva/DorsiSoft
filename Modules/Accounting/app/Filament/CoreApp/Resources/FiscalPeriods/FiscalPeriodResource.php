<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages\ListFiscalPeriods;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Pages\ViewFiscalPeriod;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Schemas\FiscalPeriodForm;
use Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Tables\FiscalPeriodsTable;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Core\Traits\HasActiveIcon;
use UnitEnum;

final class FiscalPeriodResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = FiscalPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $form): Schema
    {
        return FiscalPeriodForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return FiscalPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiscalPeriods::route('/'),
            // 'create' => CreateFiscalPeriod::route('/create'),
            // 'edit' => EditFiscalPeriod::route('/{record}/edit'),
            'view' => ViewFiscalPeriod::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Fiscal Period');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Fiscal Periods');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Accounting');
    }
}
