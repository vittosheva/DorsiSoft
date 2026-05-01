<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Pages\CreateEstablishment;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Pages\EditEstablishment;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Pages\ListEstablishments;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\RelationManagers\EmissionPointsRelationManager;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Schemas\EstablishmentForm;
use Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Tables\EstablishmentsTable;
use Modules\Core\Models\Establishment;
use Modules\Core\Traits\HasActiveIcon;
use UnitEnum;

final class EstablishmentResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = Establishment::class;

    protected static ?string $tenantOwnershipRelationshipName = 'company';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'emissionPoints:id,code,name,establishment_id',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ])
            ->withCount('emissionPoints');
    }

    public static function form(Schema $schema): Schema
    {
        return EstablishmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstablishmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EmissionPointsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstablishments::route('/'),
            // 'create' => CreateEstablishment::route('/create'),
            'edit' => EditEstablishment::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Establishment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Establishments');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Settings');
    }
}
