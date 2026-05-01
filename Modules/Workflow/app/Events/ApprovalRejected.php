<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Workflow\Models\ApprovalRecord;

final class ApprovalRejected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ApprovalRecord $record,
        public readonly string $flowKey,
        public readonly string $step,
    ) {}
}
