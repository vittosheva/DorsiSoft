<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\JournalEntry;

final class JournalReferenceGenerator
{
    public function next(int $fiscalPeriodId): string
    {
        $year = now()->format('Y');

        $last = JournalEntry::query()
            ->where('fiscal_period_id', $fiscalPeriodId)
            ->whereYear('created_at', $year)
            ->max('id');

        $sequence = ($last ?? 0) + 1;

        return sprintf('JE-%s-%06d', $year, $sequence);
    }
}
