<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Company;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Core\Models\Scopes\TenantScope;

final class EstablishmentSyncService
{
    /**
     * Synchronise the establishments for $company against $rows.
     *
     * - New rows (no 'id') create new Establishment records.
     * - Existing rows (with 'id') update the matching record.
     * - Rows no longer present are deleted along with their emission points.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function sync(Company $company, array $rows, string $source = 'sync'): void
    {
        DB::transaction(function () use ($company, $rows, $source): void {
            $company->loadMissing('establishments:id,company_id');

            $existingIds = $company->establishments
                ->pluck('id')
                ->map(static fn ($value): int => (int) $value)
                ->all();

            $requestedIds = collect($rows)
                ->pluck('id')
                ->filter(static fn (mixed $id): bool => filled($id))
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $establishmentsById = $requestedIds === []
                ? collect()
                : Establishment::withoutGlobalScopes()
                    ->where('company_id', $company->getKey())
                    ->whereIn('id', $requestedIds)
                    ->get()
                    ->keyBy('id');

            $keepIds = [];

            foreach ($rows as $row) {
                Log::debug('EstablishmentSyncService: incoming is_main', [
                    'row_id' => $row['id'] ?? null,
                    'is_main' => $row['is_main'] ?? null,
                ]);
                $establishmentCode = (string) ($row['establishment_code'] ?? '');
                $emissionPointCode = (string) ($row['emission_point_code'] ?? '');

                if ($establishmentCode === '' || $emissionPointCode === '') {
                    continue;
                }

                $nameSource = (string) ($row['name_source'] ?? 'manual');
                $name = isset($row['name']) ? mb_trim((string) $row['name']) : '';

                if (in_array($nameSource, ['sri', 'fallback'], true) && $name === '') {
                    $name = 'N/A';
                    $nameSource = 'fallback';
                }

                if ($nameSource === 'manual' && $name === '') {
                    continue;
                }

                $establishmentId = isset($row['id']) ? (int) $row['id'] : null;
                $establishment = $establishmentId ? $establishmentsById->get($establishmentId) : null;

                if ($establishment === null) {
                    $establishment = new Establishment;
                    $establishment->company_id = $company->getKey();
                }

                $currentMetadata = is_array($establishment->metadata) ? $establishment->metadata : [];

                $establishment->fill([
                    'code' => $establishmentCode,
                    'name' => $name,
                    'address' => $row['address'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'is_main' => (bool) ($row['is_main'] ?? false),
                    'metadata' => [
                        ...$currentMetadata,
                        'source' => $source,
                        'name_source' => $nameSource,
                    ],
                ]);

                if ($establishment->isDirty() || ! $establishment->exists) {
                    $establishment->save();
                }

                $establishment->emissionPoints()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('code', '!=', $emissionPointCode)
                    ->delete();

                $establishment->emissionPoints()
                    ->withoutGlobalScope(TenantScope::class)
                    ->updateOrCreate(
                        ['code' => $emissionPointCode],
                        [
                            'company_id' => $company->getKey(),
                            'name' => null,
                            'is_default' => true,
                            'is_active' => (bool) ($row['is_active'] ?? true),
                        ],
                    );

                $keepIds[] = (int) $establishment->getKey();
            }

            $removeIds = array_values(array_diff($existingIds, $keepIds));

            if ($removeIds !== []) {
                EmissionPoint::withoutGlobalScope(TenantScope::class)
                    ->whereIn('establishment_id', $removeIds)
                    ->delete();

                Establishment::withoutGlobalScope(TenantScope::class)
                    ->whereIn('id', $removeIds)
                    ->delete();
            }
        });
    }
}
