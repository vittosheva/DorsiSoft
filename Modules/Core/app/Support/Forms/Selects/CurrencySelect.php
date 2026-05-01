<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Currency;

final class CurrencySelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->relationship(
                $this->getName() === 'default_currency_id' ? 'defaultCurrency' : 'currency',
                'code',
                fn (Builder $query) => $query
                    ->select(['id', 'code', 'name'])
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
            )
            ->getOptionLabelFromRecordUsing(fn (?Currency $record): ?string => $record ? "{$record->code} - {$record->name}" : null)
            ->searchable()
            ->preload()
            ->default(function (?Currency $record) {
                // 1. If editing an existing record and currency_id is set, use it
                if ($record && $record->currency_id) {
                    return $record->currency_id;
                }

                // 2. Try to get from company's default currency
                $company = filament()->getTenant();
                if ($company && $company->default_currency_id) {
                    return $company->default_currency_id;
                }

                // 3. Fallback to system default currency
                return Currency::query()
                    ->select(['id', 'is_default'])
                    ->where('is_default', true)
                    ->value('id');
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'currency_id';
    }
}
