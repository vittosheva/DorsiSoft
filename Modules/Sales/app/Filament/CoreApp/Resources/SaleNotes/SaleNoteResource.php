<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\SaleNotes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages\CreateSaleNote;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages\EditSaleNote;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages\ListSaleNotes;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Pages\ViewSaleNote;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Schemas\SaleNoteForm;
use Modules\Sales\Filament\CoreApp\Resources\SaleNotes\Tables\SaleNotesTable;
use Modules\Sales\Models\SaleNote;
use UnitEnum;

final class SaleNoteResource extends Resource
{
    protected static ?string $model = SaleNote::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                // 'documentType:id,code,name',
                'company:id,default_currency_id,logo_pdf_url,legal_name,ruc,phone,tax_address',
                'company.defaultCurrency:id,code',
                'businessPartner:id,legal_name,identification_number,tax_address',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SaleNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaleNotes::route('/'),
            'create' => CreateSaleNote::route('/create'),
            'edit' => EditSaleNote::route('/{record}/edit'),
            'view' => ViewSaleNote::route('/{record}'),
        ];
    }

    public static function getLabel(): ?string
    {
        return __('Sale Note');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Sale Notes');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
