<?php

declare(strict_types=1);

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\People\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class SystemAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => 'system'],
            ['company_id' => null],
        );

        // Ensure company_id is null (global, not company-scoped)
        if ($role->company_id !== null) {
            $role->company_id = null;
            $role->save();
        }

        $permissions = [
            'tax_catalogs.view',
            'tax_catalogs.create',
            'tax_catalogs.update',
            'tax_catalogs.delete',
            'tax_definitions.view',
            'tax_definitions.create',
            'tax_definitions.update',
            'tax_definitions.delete',
            'tax_rules.view',
            'tax_rules.create',
            'tax_rules.update',
            'tax_rules.delete',
            'tax_rule_lines.view',
            'tax_rule_lines.create',
            'tax_rule_lines.update',
            'tax_rule_lines.delete',
            'tax_withholding_rates.view',
            'tax_withholding_rates.create',
            'tax_withholding_rates.update',
            'tax_withholding_rates.delete',
            'tax_withholding_rules.view',
            'tax_withholding_rules.create',
            'tax_withholding_rules.update',
            'tax_withholding_rules.delete',
            'sri_catalogs.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'system']);
        }

        $role->syncPermissions($permissions);

        setPermissionsTeamId(null);

        $adminUser = User::where('email', 'admin@dorsi.test')->first();

        if ($adminUser) {
            $adminUser->assignRole($role);
        }
    }
}
