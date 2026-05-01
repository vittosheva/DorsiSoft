<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Workflow\Events\ApprovalGranted;
use Modules\Workflow\Events\ApprovalRejected;
use Modules\Workflow\Events\ApprovalRequested;
use Modules\Workflow\Listeners\NotifyApproversOnApprovalRequested;
use Modules\Workflow\Listeners\NotifyDocumentCreatorOnApprovalGranted;
use Modules\Workflow\Listeners\NotifyDocumentCreatorOnApprovalRejected;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ApprovalGranted::class => [
            NotifyDocumentCreatorOnApprovalGranted::class,
        ],
        ApprovalRejected::class => [
            NotifyDocumentCreatorOnApprovalRejected::class,
        ],
        ApprovalRequested::class => [
            NotifyApproversOnApprovalRequested::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
