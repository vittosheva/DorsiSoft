<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\InventoryMovement;

final class InventoryMovementCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly InventoryMovement $movement,
    ) {}
}
