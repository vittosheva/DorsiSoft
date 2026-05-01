<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use RuntimeException;

final class DuplicateSerialException extends RuntimeException
{
    public function __construct(public readonly string $serialNumber)
    {
        parent::__construct("Serial number '{$serialNumber}' already exists for this product.");
    }
}
