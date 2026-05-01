<?php

declare(strict_types=1);

namespace Modules\Finance\Observers;

use Modules\Finance\Services\TaxRuleEngine;
use Modules\System\Models\TaxRule;

final class TaxRuleObserver
{
    public function __construct(private readonly TaxRuleEngine $engine) {}

    public function saved(TaxRule $rule): void
    {
        $this->engine->invalidateCache();
    }

    public function deleted(TaxRule $rule): void
    {
        $this->engine->invalidateCache();
    }
}
