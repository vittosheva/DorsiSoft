<?php

declare(strict_types=1);

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\Company;
use Modules\People\Services\TenantRoleProvisioner;

final class TeamRolesSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantRoleProvisioner = app(TenantRoleProvisioner::class);
        $synchronized = 0;

        Company::query()
            ->select(['id'])
            ->chunkById(100, function ($companies) use ($tenantRoleProvisioner, &$synchronized): void {
                foreach ($companies as $company) {
                    $synchronized += $this->countPermissionProvisioningRecords(
                        (int) $company->id,
                        function () use ($tenantRoleProvisioner, $company): void {
                            $tenantRoleProvisioner->provisionForCompany((int) $company->id);
                        }
                    );
                }
            });

        $this->reportSynchronized($synchronized);
    }
}
