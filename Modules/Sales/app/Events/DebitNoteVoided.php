<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\DebitNote;

final class DebitNoteVoided
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly DebitNote $debitNote,
    ) {}
}
