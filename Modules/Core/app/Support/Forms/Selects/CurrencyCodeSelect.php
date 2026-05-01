<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Currency;

final class CurrencyCodeSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(fn (): array => self::resolveOptions())
            ->default(filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
            ->searchable()
            ->preload()
            ->wrapOptionLabels(false)
            ->prefixIcon(Heroicon::CurrencyDollar);
    }

    public static function getDefaultName(): ?string
    {
        return 'currency_code';
    }

    /**
     * @param  array<int, string>  $codes
     */
    public function optionsForCodes(array $codes): static
    {
        $normalizedCodes = array_values(array_filter(array_map(
            static fn (mixed $code): string => mb_strtoupper((string) $code),
            $codes,
        )));

        $this->options(fn (): array => self::resolveOptions($normalizedCodes));

        return $this;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<string, string>
     */
    private static function resolveOptions(array $codes = []): array
    {
        return Currency::query()
            ->when(
                $codes !== [],
                fn (Builder $query) => $query->whereIn('code', $codes),
            )
            ->orderBy('code')
            ->pluck('name', 'code')
            ->mapWithKeys(fn (string $name, string $code): array => [
                $code => "{$code} — {$name}",
            ])
            ->all();
    }
}
