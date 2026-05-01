<?php

declare(strict_types=1);

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Enums\SubscriptionPlanEnum;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanySubscription;
use Modules\People\Enums\RoleEnum;
use Modules\People\Models\User;
use Modules\People\Services\TenantRoleProvisioner;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PeopleDemoUsersSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = 0;
        $updated = 0;

        $demoCompanies = $this->seedDemoCompanies($created, $updated);
        $defaultCompany = $demoCompanies[0] ?? null;

        foreach ($demoCompanies as $company) {
            $this->seedSubscriptionHistoryForCompany($company, $created, $updated);
            $created += $this->countPermissionProvisioningRecords(
                (int) $company->getKey(),
                function () use ($company): void {
                    app(TenantRoleProvisioner::class)->provisionForCompany((int) $company->getKey());
                }
            );
            $this->seedUsersForCompany($company, $created, $updated);
        }

        if ($defaultCompany instanceof Company) {
            $updated += $this->assignDefaultTenantToStandaloneUsers($defaultCompany);
        }

        $updated += $this->syncPrimaryCompanyIdForTenantUsers();

        $this->assertNoOrphanActiveUsers();

        setPermissionsTeamId(null);

        $this->reportCreatedAndUpdated($created, $updated);
    }

    protected function seedSubscriptionHistoryForCompany(Company $company, int &$created, int &$updated): void
    {
        if ($company->ruc === '0922895859001') {
            $subscription = CompanySubscription::query()->updateOrCreate(
                ['company_id' => $company->id, 'starts_at' => '2023-01-01 00:00:00'],
                [
                    'plan_code' => SubscriptionPlanEnum::Basic->value,
                    'status' => 'canceled',
                    'billing_cycle' => 'yearly',
                    'ends_at' => '2023-12-31 23:59:59',
                    'metadata' => ['reason' => 'renovación anual'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($subscription, $created, $updated);

            $subscription = CompanySubscription::query()->updateOrCreate(
                ['company_id' => $company->id, 'starts_at' => '2024-01-01 00:00:00'],
                [
                    'plan_code' => SubscriptionPlanEnum::Pro->value,
                    'status' => 'canceled',
                    'billing_cycle' => 'yearly',
                    'ends_at' => '2024-12-31 23:59:59',
                    'metadata' => ['reason' => 'ajuste presupuestario'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($subscription, $created, $updated);

            $subscription = CompanySubscription::query()->updateOrCreate(
                ['company_id' => $company->id, 'starts_at' => '2025-01-01 00:00:00'],
                [
                    'plan_code' => SubscriptionPlanEnum::Basic->value,
                    'status' => 'canceled',
                    'billing_cycle' => 'yearly',
                    'ends_at' => '2025-12-31 23:59:59',
                    'metadata' => ['reason' => 'reducción temporal de plan'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($subscription, $created, $updated);

            $subscription = CompanySubscription::query()->updateOrCreate(
                ['company_id' => $company->id, 'starts_at' => '2026-01-01 00:00:00'],
                [
                    'plan_code' => SubscriptionPlanEnum::Enterprise->value,
                    'status' => 'active',
                    'billing_cycle' => 'yearly',
                    'ends_at' => null,
                    'metadata' => ['reason' => 'expansión de escala'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($subscription, $created, $updated);

            return;
        }

        $subscription = CompanySubscription::query()->updateOrCreate(
            ['company_id' => $company->id, 'starts_at' => '2026-01-01 00:00:00'],
            [
                'plan_code' => SubscriptionPlanEnum::Pro->value,
                'status' => 'active',
                'billing_cycle' => 'yearly',
                'ends_at' => null,
                'metadata' => ['reason' => 'nueva suscripción'],
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($subscription, $created, $updated);
    }

    protected function seedDemoCompanies(int &$created, int &$updated): array
    {
        $company = Company::query()->updateOrCreate(
            ['ruc' => '0922895859001'],
            [
                'legal_name' => 'Vittorio Dormi Delgado',
                'trade_name' => 'DORSI',
                'email' => 'vittoriodormi83@gmail.com',
                'phone' => '+593991582148',
                'tax_address' => 'URB LA JOYA ETAPA ZAFIRO',
                'tax_regime' => 'RIMPE Emprendedor',
                'timezone' => 'America/Guayaquil',
                'sri_environment' => 'pruebas',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($company, $created, $updated);

        return [$company];
    }

    protected function seedUsersForCompany(Company $company, int &$created, int &$updated): void
    {
        $slug = str($company->trade_name ?? $company->legal_name)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/u', '.')
            ->trim('.');

        $owner = User::query()->updateOrCreate(
            ['email' => "owner.{$slug}@erp.test", 'company_id' => $company->id],
            [
                'code' => mb_strtoupper(mb_substr((string) $slug, 0, 3)).'001',
                'name' => "{$company->trade_name} Propietario",
                'language' => 'es',
                'timezone' => 'America/Guayaquil',
                'is_allowed_to_login' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($owner, $created, $updated);

        $operationsManager = User::query()->updateOrCreate(
            ['email' => "ops.{$slug}@erp.test", 'company_id' => $company->id],
            [
                'code' => mb_strtoupper(mb_substr((string) $slug, 0, 3)).'002',
                'name' => "{$company->trade_name} Operaciones",
                'language' => 'es',
                'timezone' => 'America/Guayaquil',
                'is_allowed_to_login' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($operationsManager, $created, $updated);

        $accountant = User::query()->updateOrCreate(
            ['email' => "accounting.{$slug}@erp.test", 'company_id' => $company->id],
            [
                'code' => mb_strtoupper(mb_substr((string) $slug, 0, 3)).'003',
                'name' => "{$company->trade_name} Contador",
                'language' => 'es',
                'timezone' => 'America/Guayaquil',
                'is_allowed_to_login' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($accountant, $created, $updated);

        $salesExecutive = User::query()->updateOrCreate(
            ['email' => "sales.{$slug}@erp.test", 'company_id' => $company->id],
            [
                'code' => mb_strtoupper(mb_substr((string) $slug, 0, 3)).'004',
                'name' => "{$company->trade_name} Ventas",
                'language' => 'es',
                'timezone' => 'America/Guayaquil',
                'is_allowed_to_login' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($salesExecutive, $created, $updated);

        $inventoryAnalyst = User::query()->updateOrCreate(
            ['email' => "inventory.{$slug}@erp.test", 'company_id' => $company->id],
            [
                'code' => mb_strtoupper(mb_substr((string) $slug, 0, 3)).'005',
                'name' => "{$company->trade_name} Inventario",
                'language' => 'es',
                'timezone' => 'America/Guayaquil',
                'is_allowed_to_login' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($inventoryAnalyst, $created, $updated);

        $company->users()->syncWithoutDetaching([
            $owner->id,
            $operationsManager->id,
            $accountant->id,
            $salesExecutive->id,
            $inventoryAnalyst->id,
        ]);

        setPermissionsTeamId($company->id);

        $owner->syncRoles([RoleEnum::ADMIN->value]);
        $operationsManager->syncRoles([RoleEnum::SUPERVISOR->value]);
        $accountant->syncRoles([RoleEnum::ACCOUNTANT->value]);
        $salesExecutive->syncRoles([RoleEnum::SALES_REP->value]);
        $inventoryAnalyst->syncRoles([RoleEnum::WAREHOUSE_KEEPER->value]);
    }

    private function assertNoOrphanActiveUsers(): void
    {
        $orphanEmails = User::query()
            ->where('is_allowed_to_login', true)
            ->where('is_active', true)
            ->whereDoesntHave('companies')
            ->pluck('email')
            ->all();

        if ($orphanEmails !== []) {
            throw new RuntimeException(__('Active users without tenant assignment: :emails', ['emails' => implode(', ', $orphanEmails)]));
        }
    }

    private function syncPrimaryCompanyIdForTenantUsers(): int
    {
        $users = User::query()
            ->select(['id', 'company_id'])
            ->where('is_allowed_to_login', true)
            ->where('is_active', true)
            ->whereNull('company_id')
            ->whereHas('companies')
            ->with(['companies' => fn ($query) => $query->select('core_companies.id')->orderBy('core_companies.id')->limit(1)])
            ->get();

        $updated = 0;

        foreach ($users as $user) {
            $tenantId = $user->companies->first()?->id;

            if (blank($tenantId)) {
                continue;
            }

            $user->forceFill([
                'company_id' => (int) $tenantId,
                'created_by' => 1,
                'updated_by' => 1,
            ])->save();
            $updated++;
        }

        return $updated;
    }

    private function assignDefaultTenantToStandaloneUsers(Company $defaultCompany): int
    {
        $standaloneUsers = User::query()
            ->select(['id', 'company_id'])
            ->whereDoesntHave('companies')
            ->where('is_allowed_to_login', true)
            ->where('is_active', true)
            ->get();

        if ($standaloneUsers->isEmpty()) {
            return 0;
        }

        setPermissionsTeamId((int) $defaultCompany->getKey());

        Role::findOrCreate(RoleEnum::ADMIN->value, 'web');

        $updated = 0;

        foreach ($standaloneUsers as $user) {
            $defaultCompany->users()->syncWithoutDetaching([$user->getKey()]);
            $user->forceFill([
                'company_id' => $defaultCompany->getKey(),
                'created_by' => 1,
                'updated_by' => 1,
            ])->save();
            $user->syncRoles([RoleEnum::ADMIN->value]);
            $updated++;
        }

        return $updated;
    }
}
