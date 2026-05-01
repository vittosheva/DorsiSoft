<?php

declare(strict_types=1);

namespace Modules\Core\Support\Tables\Columns;

use Closure;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;

final class MoneyTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('0.00')
            ->alignment(Alignment::Right)
            ->weight(FontWeight::Bold)
            ->sortable()
            ->summarize([
                Sum::make()->money(),
            ]);
    }

    public function withoutDefaultSummarizer(): static
    {
        $this->summarizers = [];

        return $this;
    }

    /**
     * Set the currency code used for money formatting.
     * Accepts a static ISO 4217 code string or a closure receiving the record.
     */
    public function currencyCode(string|Closure $code): static
    {
        $this->money($code, 0, app()->getLocale());

        return $this;
    }
}
