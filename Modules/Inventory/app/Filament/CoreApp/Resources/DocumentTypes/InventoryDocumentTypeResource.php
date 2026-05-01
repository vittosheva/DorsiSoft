<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages\CreateDocumentType;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages\EditDocumentType;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Pages\ListDocumentTypes;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Schemas\DocumentTypeForm;
use Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Tables\DocumentTypesTable;
use Modules\Inventory\Models\InventoryDocumentType;
use UnitEnum;

final class InventoryDocumentTypeResource extends Resource
{
    protected static ?string $model = InventoryDocumentType::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?int $navigationSort = 95;

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTypes::route('/'),
            // 'create' => CreateDocumentType::route('/create'),
            // 'edit' => EditDocumentType::route('/{record}/edit'),
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
        return __('Inventory');
    }
}
