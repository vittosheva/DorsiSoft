<?php

declare(strict_types=1);

namespace Modules\People\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Core\Events\CompanyCreated;
use Modules\People\Services\TenantRoleProvisioner;

final class ProvisionRolesOnCompanyCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(private readonly TenantRoleProvisioner $tenantRoleProvisioner) {}

    public function handle(CompanyCreated $event): void
    {
        $this->tenantRoleProvisioner->provisionForCompany((int) $event->company->getKey());
    }
}
