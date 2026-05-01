<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Tables\Filters;

use Filament\Tables\Filters\TernaryFilter;

final class VoidedFilter extends TernaryFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->attribute('voided_at')
            ->nullable()
            ->columnSpan(1);
    }

    public static function getDefaultName(): ?string
    {
        return 'voided';
    }
}
