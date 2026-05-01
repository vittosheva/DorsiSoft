<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

abstract class BaseModuleEventServiceProvider extends ServiceProvider
{
    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
