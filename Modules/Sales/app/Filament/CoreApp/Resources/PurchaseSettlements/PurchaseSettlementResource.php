<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages\CreatePurchaseSettlement;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages\EditPurchaseSettlement;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages\ListPurchaseSettlements;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Pages\ViewPurchaseSettlement;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Schemas\PurchaseSettlementForm;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\Tables\PurchaseSettlementsTable;
use Modules\Sales\Models\PurchaseSettlement;
use UnitEnum;

final class PurchaseSettlementResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = PurchaseSettlement::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 80;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'documentType:id,code,name',
                'supplier:id,legal_name,identification_number,tax_address',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseSettlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseSettlementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseSettlements::route('/'),
            'create' => CreatePurchaseSettlement::route('/create'),
            'edit' => EditPurchaseSettlement::route('/{record}/edit'),
            // 'view' => ViewPurchaseSettlement::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Purchase Settlement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Purchase Settlements');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
