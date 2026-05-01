<?php

declare(strict_types=1);

namespace Modules\Finance\Support\Forms\Selects;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Filament\CoreApp\Resources\Taxes\TaxResource;
use Modules\Finance\Models\Tax;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class TaxSelect extends Select
{
    protected bool $onlyActive = true;

    protected TaxTypeEnum|string|null $taxType = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(fn (): array => $this->getTaxOptions())
            ->getSearchResultsUsing(fn (string $search): array => $this->getTaxOptions($search))
            ->getOptionLabelUsing(fn (mixed $value): ?string => $this->getTaxLabel($value))
            ->searchable()
            ->preload()
            ->nullable()
            ->prefixIcon(TaxResource::getNavigationIcon());
    }

    public static function getDefaultName(): ?string
    {
        return 'tax_id';
    }

    public function includeInactive(): static
    {
        $this->onlyActive = false;

        return $this;
    }

    public function forType(TaxTypeEnum|string|null $type): static
    {
        $this->taxType = $type;

        return $this;
    }

    private static function formatLabel(Tax $tax): string
    {
        $calculationType = $tax->calculation_type instanceof TaxCalculationTypeEnum
            ? $tax->calculation_type
            : TaxCalculationTypeEnum::tryFrom((string) $tax->calculation_type);

        $formattedRate = $calculationType === TaxCalculationTypeEnum::Fixed
            ? '$'.number_format((float) $tax->rate, 2)
            : number_format((float) $tax->rate, 2).' %';

        $type = $tax->type instanceof TaxTypeEnum ? $tax->type->value : (string) $tax->type;

        return "{$tax->name} [{$type} | {$formattedRate}]";
    }

    /**
     * @return array<int, string>
     */
    private function getTaxOptions(?string $search = null): array
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId === null) {
            return [];
        }

        if (blank($search)) {
            return Cache::remember(
                $this->getCacheKey($tenantId),
                now()->addMinutes(5),
                fn (): array => $this->resolveTaxOptions($tenantId),
            );
        }

        return $this->resolveTaxOptions($tenantId, $search);
    }

    private function getTaxLabel(mixed $value): ?string
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (($tenantId === null) || blank($value)) {
            return null;
        }

        $tax = Tax::query()
            ->select(['id', 'company_id', 'name', 'type', 'rate', 'calculation_type'])
            ->where('company_id', $tenantId)
            ->whereKey($value)
            ->first();

        return $tax ? self::formatLabel($tax) : null;
    }

    private function getCacheKey(int|string $tenantId): string
    {
        return sprintf(
            'finance.tax-options.%s.%s.%s',
            $tenantId,
            $this->onlyActive ? 'active' : 'all',
            $this->taxType instanceof TaxTypeEnum ? $this->taxType->value : ($this->taxType ?? 'any'),
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveTaxOptions(int|string $tenantId, ?string $search = null): array
    {
        return Tax::query()
            ->select(['id', 'company_id', 'code', 'name', 'type', 'rate', 'calculation_type', 'is_active'])
            ->where('company_id', $tenantId)
            ->when($this->onlyActive, fn (Builder $query) => $query->active())
            ->when($this->taxType !== null, fn (Builder $query) => $query->byType($this->taxType))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('type')
            ->orderBy('rate')
            ->orderBy('name')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
            ->get()
            ->mapWithKeys(fn (Tax $tax): array => [$tax->id => self::formatLabel($tax)])
            ->all();
    }
}
