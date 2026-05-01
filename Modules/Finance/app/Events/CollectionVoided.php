<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Models\Collection;

final class CollectionVoided
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Collection $collection,
    ) {}
}
