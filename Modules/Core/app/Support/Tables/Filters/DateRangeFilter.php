<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Filters;

use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter as BaseDateRangeFilter;

final class DateRangeFilter extends BaseDateRangeFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->maxDate(now())
            ->placeholder(__('Select date range'))
            ->columnSpan(2);
    }
}
