<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Forms\Concerns;

use Closure;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Services\DocumentSequentialService;

trait HasSriEstablishmentFields
{
    /**
     * Returns an afterStateUpdated closure for establishment_code that resets
     * both emission_point_code and sequential_number when the establishment changes.
     */
    protected static function resetSequenceOnEstablishmentChange(): Closure
    {
        return function (Set $set): void {
            $set('emission_point_code', null);
            $set('sequential_number', null);
        };
    }

    /**
     * Returns an afterStateUpdated closure for emission_point_code that
     * auto-suggests the next sequential number (create operation only).
     */
    protected static function suggestSequentialOnEmissionPointChange(SriDocumentTypeEnum $documentType): Closure
    {
        return function (?string $state, Get $get, Set $set, string $operation) use ($documentType): void {
            if ($operation !== 'create' || blank($state) || blank($get('establishment_code'))) {
                return;
            }

            $companyId = Filament::getTenant()?->getKey();

            if (! $companyId) {
                return;
            }

            $set(
                'sequential_number',
                app(DocumentSequentialService::class)->suggest(
                    companyId: $companyId,
                    establishmentCode: $get('establishment_code'),
                    emissionPointCode: $state,
                    documentType: $documentType,
                ),
            );
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function resolveEstablishmentOptions(): array
    {
        $companyId = Filament::getTenant()?->getKey();

        if (! $companyId) {
            return [];
        }

        return Establishment::query()
            ->select(['code', 'name'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
            ->get()
            ->mapWithKeys(fn (Establishment $e): array => [
                $e->code => filled($e->name) ? "{$e->code} — {$e->name}" : $e->code,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function resolveEmissionPointOptions(?string $establishmentCode): array
    {
        if (blank($establishmentCode)) {
            return [];
        }

        $companyId = Filament::getTenant()?->getKey();

        return EmissionPoint::query()
            ->select(['code', 'name'])
            ->where('company_id', $companyId)
            ->whereHas(
                'establishment',
                fn ($q) => $q->where('code', $establishmentCode),
            )
            ->where('is_active', true)
            ->orderBy('code')
            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
            ->get()
            ->mapWithKeys(fn (EmissionPoint $e): array => [
                $e->code => filled($e->name) ? "{$e->code} — {$e->name}" : $e->code,
            ])
            ->all();
    }
}
