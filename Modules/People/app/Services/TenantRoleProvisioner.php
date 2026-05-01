<?php

declare(strict_types=1);

namespace Modules\People\Services;

use Modules\People\Enums\RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TenantRoleProvisioner
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        RoleEnum::ADMIN->name => [],
        RoleEnum::SUPERVISOR->name => [
            'users.view',
            'roles.view',
            'companies.view',
            'customers.view',
            'customers.create',
            'customers.update',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'business_partners.view',
            'business_partners.create',
            'business_partners.update',
            'products.view',
            'products.create',
            'products.update',
            'inventory.view',
            'inventory.adjust',
            'sales.view',
            'sales.create',
            'sales.cancel',
            'collections.view',
            'reports.view',
            'settings.view',
            'establishments.view',
            'establishments.create',
            'establishments.update',
            'establishments.delete',
            'establishments.restore',
        ],
        RoleEnum::ACCOUNTANT->name => [
            'companies.view',
            'customers.view',
            'suppliers.view',
            'business_partners.view',
            'products.view',
            'inventory.view',
            'sales.view',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send',
            'credit_notes.view',
            'credit_notes.create',
            'credit_notes.update',
            'credit_notes.delete',
            'credit_notes.restore',
            'debit_notes.view',
            'debit_notes.create',
            'debit_notes.update',
            'debit_notes.delete',
            'debit_notes.restore',
            'establishments.view',
            'collections.view',
            'collections.create',
            'collections.update',
            'reports.view',
            'finance.view',
            'finance.manage',
            'accounting.view',
            'accounting.manage',
            'taxes.view',
            'taxes.create',
            'taxes.update',
            'taxes.delete',
            'taxes.restore',
            'chart_of_accounts.view',
            'journal_entries.view',
            'journal_entries.create',
            'journal_entries.update',
            'journal_entries.approve',
            'journal_entries.void',
        ],
        RoleEnum::SALES_REP->name => [
            'customers.view',
            'customers.create',
            'customers.update',
            'business_partners.view',
            'business_partners.create',
            'business_partners.update',
            'products.view',
            'inventory.view',
            'sales.view',
            'sales.create',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send',
            'collections.view',
            'reports.view',
        ],
        RoleEnum::WAREHOUSE_KEEPER->name => [
            'products.view',
            'products.create',
            'products.update',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'business_partners.view',
            'business_partners.create',
            'business_partners.update',
            'inventory.view',
            'inventory.adjust',
            'reports.view',
        ],
        RoleEnum::BILLING_CLERK->name => [
            'customers.view',
            'customers.create',
            'customers.update',
            'business_partners.view',
            'business_partners.create',
            'business_partners.update',
            'products.view',
            'inventory.view',
            'sales.view',
            'sales.create',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send',
            'credit_notes.view',
            'credit_notes.create',
            'credit_notes.update',
            'credit_notes.delete',
            'debit_notes.view',
            'debit_notes.create',
            'debit_notes.update',
            'debit_notes.delete',
            'establishments.view',
            'collections.view',
            'collections.create',
            'collections.update',
            'reports.view',
        ],
        RoleEnum::CASHIER->name => [
            'customers.view',
            'products.view',
            'inventory.view',
            'sales.view',
            'sales.create',
            'pos.open_shift',
            'pos.close_shift',
            'pos.refund',
            'pos.discount',
            'pos.reprint',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send',
            'collections.view',
            'collections.create',
        ],
        RoleEnum::SUPPLIER->name => [
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'business_partners.view',
            'products.view',
            'inventory.view',
            'reports.view',
        ],
        RoleEnum::AUDITOR->name => [
            'users.view',
            'roles.view',
            'companies.view',
            'customers.view',
            'suppliers.view',
            'business_partners.view',
            'products.view',
            'inventory.view',
            'sales.view',
            'invoices.view',
            'credit_notes.view',
            'debit_notes.view',
            'establishments.view',
            'collections.view',
            'reports.view',
            'finance.view',
            'accounting.view',
            'taxes.view',
            'settings.view',
        ],
    ];

    public function provisionForCompany(int $companyId): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        setPermissionsTeamId($companyId);

        try {
            $allPermissions = Permission::query()->select('name')->pluck('name')->all();

            // Crear todos los roles definidos en RoleEnum, aunque no tengan permisos asignados
            foreach (RoleEnum::cases() as $roleEnum) {
                Role::updateOrCreate(
                    [
                        'name' => $roleEnum->value,
                        'company_id' => $companyId,
                        'guard_name' => 'web',
                    ],
                    [
                        'display_name' => $roleEnum->displayName(),
                        'description' => $roleEnum->description(),
                    ]
                );
            }

            // Asignar permisos a los roles que los tengan definidos
            foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
                $roleEnum = RoleEnum::tryFromName($roleName);
                if (! $roleEnum) {
                    continue;
                }
                $role = Role::where([
                    'name' => $roleEnum->value,
                    'company_id' => $companyId,
                    'guard_name' => 'web',
                ])->first();
                if (! $role) {
                    continue;
                }
                if ($roleEnum === RoleEnum::ADMIN) {
                    $role->syncPermissions($allPermissions);

                    continue;
                }
                $role->syncPermissions($permissions);
            }
        } finally {
            setPermissionsTeamId(null);
        }
    }
}
