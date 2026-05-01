<?php

declare(strict_types=1);

namespace Modules\People\Providers;

use Modules\Core\Events\CompanyCreated;
use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\People\Listeners\ProvisionRolesOnCompanyCreated;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        CompanyCreated::class => [
            ProvisionRolesOnCompanyCreated::class,
        ],
    ];
}
