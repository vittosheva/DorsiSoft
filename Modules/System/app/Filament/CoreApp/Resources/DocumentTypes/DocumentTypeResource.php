<?php

declare(strict_types=1);

namespace Modules\System\Filament\CoreApp\Resources\DocumentTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages\CreateDocumentType;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages\EditDocumentType;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages\ListDocumentTypes;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Pages\ViewDocumentType;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\RelationManagers\DocumentSeriesRelationManager;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Schemas\DocumentTypeForm;
use Modules\System\Filament\CoreApp\Resources\DocumentTypes\Tables\DocumentTypesTable;
use Modules\System\Models\DocumentType;
use UnitEnum;

final class DocumentTypeResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = DocumentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ])
            ->withCount('series');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentSeriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTypes::route('/'),
            'create' => CreateDocumentType::route('/create'),
            'edit' => EditDocumentType::route('/{record}/edit'),
            'view' => ViewDocumentType::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Document Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Document Types');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Settings');
    }
}
