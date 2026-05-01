<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Finance\DTOs\TaxContext;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

/**
 * Resolves which TaxDefinitions apply to a given transaction context.
 *
 * Rules are matched in ascending priority order. All conditions within a rule
 * are ANDed. For OR logic, create multiple rules with the same tax_definition_id.
 *
 * Usage:
 *   $defs = app(TaxRuleEngine::class)->resolve($context, 'VENTA');
 *   $taxes = Tax::whereIn('tax_definition_id', $defs->pluck('id'))->where('company_id', $company->id)->get();
 *   $result = app(ItemTaxComputationService::class)->compute($qty, $subtotal, $taxes);
 */
final class TaxRuleEngine
{
    private const CACHE_TTL_SECONDS = 600;

    /**
     * @return Collection<int, TaxDefinition>
     */
    public function resolve(TaxContext $context, string|TaxAppliesToEnum $appliesTo = TaxAppliesToEnum::Venta): Collection
    {
        $appliesToValue = $appliesTo instanceof TaxAppliesToEnum ? $appliesTo->value : $appliesTo;
        $date = $context->date ?? Carbon::today();
        $rules = $this->loadRules($appliesToValue, $date);

        $matched = $rules->filter(fn (TaxRule $rule): bool => $this->matchesRule($rule, $context));

        return $matched
            ->map(fn (TaxRule $rule): TaxDefinition => $rule->taxDefinition)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Invalidate cached rules for all applies_to variants.
     */
    public function invalidateCache(): void
    {
        foreach (TaxAppliesToEnum::cases() as $case) {
            Cache::forget('tax_rules.'.$case->value);
        }
    }

    /**
     * @return Collection<int, TaxRule>
     */
    private function loadRules(string $appliesToValue, Carbon $date): Collection
    {
        $cacheKey = 'tax_rules.'.$appliesToValue;

        $rules = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($appliesToValue): Collection {
            return TaxRule::query()
                ->with('taxDefinition')
                ->when(
                    $appliesToValue === TaxAppliesToEnum::Venta->value,
                    fn ($q) => $q->forSales()
                )
                ->when(
                    $appliesToValue === TaxAppliesToEnum::Compra->value,
                    fn ($q) => $q->forPurchases()
                )
                ->active()
                ->orderBy('priority')
                ->orderBy('id')
                ->get();
        });

        return $rules->filter(fn (TaxRule $rule): bool => $this->isValidAt($rule, $date))->values();
    }

    private function isValidAt(TaxRule $rule, Carbon $date): bool
    {
        if ($rule->valid_from !== null && $rule->valid_from->isAfter($date)) {
            return false;
        }

        if ($rule->valid_to !== null && $rule->valid_to->isBefore($date)) {
            return false;
        }

        return true;
    }

    private function matchesRule(TaxRule $rule, TaxContext $context): bool
    {
        $conditions = $rule->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{field: string, operator: string, value: mixed}  $condition
     */
    private function evaluateCondition(array $condition, TaxContext $context): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $expected = $condition['value'] ?? null;
        $actual = $context->get($field);

        if ($actual === null) {
            return false;
        }

        return match ($operator) {
            '=' => $actual === $expected,
            '!=' => $actual !== $expected,
            'in' => in_array($actual, (array) $expected, strict: true),
            'not_in' => ! in_array($actual, (array) $expected, strict: true),
            '>' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            '>=' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            '<' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            '<=' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            default => false,
        };
    }
}
