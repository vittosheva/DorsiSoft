<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\Collections;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Pages\CreateCollection;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Pages\EditCollection;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Pages\ListCollections;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Pages\ViewCollection;
use Modules\Finance\Filament\CoreApp\Resources\Collections\RelationManagers\CollectionAllocationsRelationManager;
use Modules\Finance\Filament\CoreApp\Resources\Collections\RelationManagers\CollectionReversalsRelationManager;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Schemas\CollectionForm;
use Modules\Finance\Filament\CoreApp\Resources\Collections\Tables\CollectionsTable;
use Modules\Finance\Models\Collection;
use UnitEnum;

final class CollectionResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Collection::class;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 90;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('allocations as allocated_amount', 'amount')
            ->with([
                'businessPartner:id,identification_number,legal_name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CollectionAllocationsRelationManager::class,
            CollectionReversalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
            'view' => ViewCollection::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Collection');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Collections');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Finance');
    }
}
