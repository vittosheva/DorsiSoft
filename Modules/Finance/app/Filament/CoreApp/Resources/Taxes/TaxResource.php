<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Taxes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages\CreateTax;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages\EditTax;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Pages\ListTaxes;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Schemas\TaxForm;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\Tables\TaxesTable;
use Modules\Finance\Models\Tax;
use UnitEnum;

final class TaxResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Tax::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 90;

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
        return TaxForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxes::route('/'),
            // 'create' => CreateTax::route('/create'),
            // 'edit' => EditTax::route('/{record}/edit'),
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
        return __('Finance');
    }
}
