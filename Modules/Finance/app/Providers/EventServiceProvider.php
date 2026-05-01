<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\Finance\Events\CollectionVoided;
use Modules\Finance\Listeners\CreateCollectionAllocationReversalsOnCollectionVoided;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        CollectionVoided::class => [
            CreateCollectionAllocationReversalsOnCollectionVoided::class,
        ],
    ];
}
