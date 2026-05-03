<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Traits\HasActiveIcon;
use Modules\People\Filament\CoreApp\Resources\Users\Pages\CreateUser;
use Modules\People\Filament\CoreApp\Resources\Users\Pages\EditUser;
use Modules\People\Filament\CoreApp\Resources\Users\Pages\ListUsers;
use Modules\People\Filament\CoreApp\Resources\Users\Pages\ViewUser;
use Modules\People\Filament\CoreApp\Resources\Users\Schemas\UserForm;
use Modules\People\Filament\CoreApp\Resources\Users\Schemas\UserInfolist;
use Modules\People\Filament\CoreApp\Resources\Users\Tables\UsersTable;
use Modules\People\Models\User;
use UnitEnum;

final class UserResource extends Resource
{
    use HasActiveIcon;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'roles:id,name,display_name',
                'creator:id,name,avatar_url',
                'editor:id,name,avatar_url',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    /* public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    } */

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('People');
    }

    public static function getRelations(): array
    {
        return [];
    }
}
