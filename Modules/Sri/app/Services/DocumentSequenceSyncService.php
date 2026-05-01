<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Models\DocumentSequence;

final class DocumentSequenceSyncService
{
    public function syncForEstablishment(Establishment $establishment): void
    {
        if (! $establishment->is_active || blank($establishment->code)) {
            return;
        }

        $emissionPointCodes = EmissionPoint::query()
            ->select('code')
            ->where('company_id', $establishment->company_id)
            ->where('establishment_id', $establishment->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $this->syncForCodes(
            companyId: (int) $establishment->company_id,
            establishmentCode: (string) $establishment->code,
            emissionPointCodes: $emissionPointCodes,
        );
    }

    public function syncForEmissionPoint(EmissionPoint $emissionPoint): void
    {
        if (! $emissionPoint->is_active || blank($emissionPoint->code)) {
            return;
        }

        $establishment = Establishment::query()
            ->select(['code'])
            ->whereKey($emissionPoint->establishment_id)
            ->where('company_id', $emissionPoint->company_id)
            ->where('is_active', true)
            ->first();

        if (! $establishment || blank($establishment->code)) {
            return;
        }

        $this->syncForCodes(
            companyId: (int) $emissionPoint->company_id,
            establishmentCode: (string) $establishment->code,
            emissionPointCodes: [(string) $emissionPoint->code],
        );
    }

    /**
     * @param  array<int, string>  $emissionPointCodes
     */
    private function syncForCodes(int $companyId, string $establishmentCode, array $emissionPointCodes): void
    {
        if ($emissionPointCodes === []) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($emissionPointCodes as $emissionPointCode) {
            foreach (SriDocumentTypeEnum::cases() as $documentType) {
                $rows[] = [
                    'company_id' => $companyId,
                    'establishment_code' => $establishmentCode,
                    'emission_point_code' => $emissionPointCode,
                    'document_type' => $documentType->value,
                    'last_sequential' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DocumentSequence::query()->insertOrIgnore($chunk);
        }
    }
}
