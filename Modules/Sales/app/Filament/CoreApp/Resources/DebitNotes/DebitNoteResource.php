<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages\CreateDebitNote;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages\EditDebitNote;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages\ListDebitNotes;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages\ViewDebitNote;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\RelationManagers\InvoiceRelationManager;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Schemas\DebitNoteForm;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Tables\DebitNotesTable;
use Modules\Sales\Models\DebitNote;
use UnitEnum;

final class DebitNoteResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = DebitNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 50;

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
        return DebitNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebitNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoiceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDebitNotes::route('/'),
            'create' => CreateDebitNote::route('/create'),
            'edit' => EditDebitNote::route('/{record}/edit'),
            'view' => ViewDebitNote::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Debit Note');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Debit Notes');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Sales');
    }
}
