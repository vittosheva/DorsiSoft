<?php

declare(strict_types=1);

namespace Modules\Sales\Casts;

use Stringable;

final class FormattedMoney implements Stringable
{
    public function __construct(public readonly float $value) {}

    public function __toString(): string
    {
        return number_format($this->value, 2, '.', ',');
    }

    public function __invoke(): float
    {
        return $this->value;
    }

    public function __debugInfo(): array
    {
        return ['value' => $this->value];
    }

    public function toFloat(): float
    {
        return $this->value;
    }
}
