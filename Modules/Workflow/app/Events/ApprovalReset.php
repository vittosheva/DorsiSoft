<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ApprovalReset
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Model $approvable,
        public readonly string $flowKey,
        public readonly string $step,
        public readonly Model $resetBy,
    ) {}
}
