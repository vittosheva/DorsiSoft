<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages\CreateQuotation;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages\EditQuotation;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages\ListQuotations;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages\ViewQuotation;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Schemas\QuotationForm;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\Tables\QuotationsTable;
use Modules\Sales\Models\Quotation;
use UnitEnum;

final class QuotationResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Quotation::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'seller:id,name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return QuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'edit' => EditQuotation::route('/{record}/edit'),
            'view' => ViewQuotation::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Quotation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Quotations');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
