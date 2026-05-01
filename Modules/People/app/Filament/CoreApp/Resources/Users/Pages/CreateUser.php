<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Filament\Facades\Filament;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;
use Spatie\Permission\Models\Role;

final class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (blank($tenantId)) {
            return;
        }

        $this->record->companies()->syncWithoutDetaching([$tenantId]);

        $roleName = $this->data['role_name'] ?? null;

        if (blank($roleName)) {
            return;
        }

        setPermissionsTeamId((int) $tenantId);
        Role::findOrCreate((string) $roleName, 'web');
        $this->record->syncRoles([(string) $roleName]);
    }
}
