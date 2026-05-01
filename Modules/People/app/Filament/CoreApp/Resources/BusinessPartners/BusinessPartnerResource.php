<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages\CreateBusinessPartner;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages\EditBusinessPartner;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Pages\ListBusinessPartners;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas\BusinessPartnerForm;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Tables\BusinessPartnersTable;
use Modules\People\Models\BusinessPartner;
use UnitEnum;

final class BusinessPartnerResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = BusinessPartner::class;

    protected static ?string $recordTitleAttribute = 'legal_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'roles:id,name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return BusinessPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessPartners::route('/'),
            'create' => CreateBusinessPartner::route('/create'),
            'edit' => EditBusinessPartner::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Entity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Entities');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('People');
    }
}
