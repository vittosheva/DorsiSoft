<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages\CreateWithholding;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages\EditWithholding;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages\ListWithholdings;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages\ViewWithholding;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Schemas\WithholdingForm;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Tables\WithholdingsTable;
use Modules\Sales\Models\Withholding;
use UnitEnum;

final class WithholdingResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Withholding::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMinus;

    protected static ?int $navigationSort = 70;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('items', 'withheld_amount')
            ->with([
                'businessPartner:id,legal_name,identification_number,tax_address',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
                'company:id,default_currency_id',
                'company.defaultCurrency:id,code',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return WithholdingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WithholdingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithholdings::route('/'),
            'create' => CreateWithholding::route('/create'),
            'edit' => EditWithholding::route('/{record}/edit'),
            // 'view' => ViewWithholding::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Withholding');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Withholdings');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
