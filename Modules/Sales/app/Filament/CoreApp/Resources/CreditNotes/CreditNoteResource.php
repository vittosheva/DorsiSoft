<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages\CreateCreditNote;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages\EditCreditNote;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages\ListCreditNotes;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages\ViewCreditNote;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\RelationManagers\ApplicationsRelationManager;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Schemas\CreditNoteForm;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Tables\CreditNotesTable;
use Modules\Sales\Models\CreditNote;
use UnitEnum;

final class CreditNoteResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = CreditNote::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMinus;

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'documentType:id,code,name',
                'invoice:id,code,establishment_code,emission_point_code,sequential_number,document_type_id,company_id',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CreditNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditNotes::route('/'),
            'create' => CreateCreditNote::route('/create'),
            'edit' => EditCreditNote::route('/{record}/edit'),
            'view' => ViewCreditNote::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Credit Note');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Credit Notes');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
