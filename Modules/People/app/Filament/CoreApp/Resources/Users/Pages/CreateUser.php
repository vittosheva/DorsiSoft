<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Pages;

use Filament\Facades\Filament;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;
use Modules\People\Services\UserTenantRoleSyncService;

final class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (blank($tenantId)) {
            return;
        }

        $roleIds = (array) ($this->data['role_name'] ?? []);

        app(UserTenantRoleSyncService::class)->sync($this->record, (int) $tenantId, $roleIds);
    }
}
