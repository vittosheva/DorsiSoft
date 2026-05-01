<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Modules\Accounting\Listeners\CreateDraftJournalEntryListener;
use Modules\Core\Providers\BaseModuleEventServiceProvider;
use Modules\Sales\Events\InvoiceIssued;

final class EventServiceProvider extends BaseModuleEventServiceProvider
{
    protected $listen = [
        InvoiceIssued::class => [
            CreateDraftJournalEntryListener::class,
        ],
    ];
}
