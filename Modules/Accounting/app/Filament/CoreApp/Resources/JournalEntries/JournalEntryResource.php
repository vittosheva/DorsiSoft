<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages\CreateJournalEntry;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages\EditJournalEntry;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages\ListJournalEntries;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\RelationManagers\JournalLinesRelationManager;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Schemas\JournalEntryForm;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Tables\JournalEntriesTable;
use Modules\Accounting\Models\JournalEntry;
use Modules\Core\Traits\HasActiveIcon;
use UnitEnum;

final class JournalEntryResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return JournalEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JournalLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Journal Entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Journal Entries');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Accounting');
    }
}
