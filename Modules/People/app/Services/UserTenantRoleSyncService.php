<?php

declare(strict_types=1);

namespace Modules\People\Services;

use Modules\People\Models\User;
use Spatie\Permission\Models\Role;

final class UserTenantRoleSyncService
{
    /**
     * @param  array<int, int|string>  $roleIds
     */
    public function sync(User $user, int $tenantId, array $roleIds): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $user->companies()->syncWithoutDetaching([$tenantId]);

        $normalizedRoleIds = collect($roleIds)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        if ($normalizedRoleIds === []) {
            return;
        }

        setPermissionsTeamId($tenantId);

        try {
            $roles = Role::query()
                ->where('guard_name', 'web')
                ->where('company_id', $tenantId)
                ->whereIn('id', $normalizedRoleIds)
                ->pluck('name')
                ->all();

            $user->syncRoles($roles);
        } finally {
            setPermissionsTeamId(null);
        }
    }
}
