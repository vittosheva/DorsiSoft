<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a document enters a state that requires approval from a specific step.
 *
 * Dispatched in two situations:
 *   1. A new Approvable model is created and its flow is enabled for the tenant.
 *   2. A step is approved and the overall flow is still Pending (next step needs action).
 */
final class ApprovalRequested
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Model $approvable,
        public readonly string $flowKey,
        public readonly string $stepName,
    ) {}
}
