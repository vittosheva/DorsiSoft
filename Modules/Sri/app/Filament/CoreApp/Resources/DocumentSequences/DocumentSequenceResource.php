<?php

declare(strict_types=1);

namespace Modules\Sri\Filament\CoreApp\Resources\DocumentSequences;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages\CreateDocumentSequence;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages\ListDocumentSequences;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Pages\ViewDocumentSequence;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Schemas\DocumentSequenceForm;
use Modules\Sri\Filament\CoreApp\Resources\DocumentSequences\Tables\DocumentSequencesTable;
use Modules\Sri\Models\DocumentSequence;
use UnitEnum;

final class DocumentSequenceResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = DocumentSequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'latestHistory' => fn (HasOne $query): HasOne => $query->select([
                    'sales_document_sequence_history.id',
                    'sales_document_sequence_history.document_sequence_id',
                    'sales_document_sequence_history.performed_by',
                    'sales_document_sequence_history.created_at',
                ]),
                'latestHistory.performedBy:id,name',
            ])
            ->withCount('history')
            ->orderBy('document_type')
            ->orderBy('establishment_code')
            ->orderBy('emission_point_code');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentSequenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentSequencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentSequences::route('/'),
            // 'create' => CreateDocumentSequence::route('/create'),
            // 'edit' => EditDocumentSequence::route('/{record}/edit'),
            'view' => ViewDocumentSequence::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Document Sequence');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Document Sequences');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Settings');
    }
}
