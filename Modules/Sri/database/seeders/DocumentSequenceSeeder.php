<?php

declare(strict_types=1);

namespace Modules\Sri\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Models\DocumentSequence;

final class DocumentSequenceSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed baseline SRI sequential counters for each (company, establishment, emission point, document type).
     */
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $companyIds = DB::table('core_companies')
            ->orderBy('id')
            ->pluck('id');

        if ($companyIds->isEmpty()) {
            $this->reportSkipped('0 records created, no companies found');

            return;
        }

        foreach ($companyIds as $companyId) {
            $pairs = DB::table('core_emission_points as ep')
                ->join('core_establishments as est', 'est.id', '=', 'ep.establishment_id')
                ->where('ep.company_id', $companyId)
                ->where('est.company_id', $companyId)
                ->where('ep.is_active', true)
                ->where('est.is_active', true)
                ->whereNull('ep.deleted_at')
                ->whereNull('est.deleted_at')
                ->orderByDesc('ep.is_default')
                ->orderBy('ep.id')
                ->get([
                    'est.code as establishment_code',
                    'ep.code as emission_point_code',
                ]);

            if ($pairs->isEmpty()) {
                $this->command?->warn("DocumentSequenceSeeder: No active establishment/emission points found for company {$companyId}. Skipping.");

                continue;
            }

            foreach ($pairs as $pair) {
                foreach (SriDocumentTypeEnum::cases() as $documentType) {
                    $sequence = DocumentSequence::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'establishment_code' => $pair->establishment_code,
                            'emission_point_code' => $pair->emission_point_code,
                            'document_type' => $documentType,
                        ],
                        [
                            'last_sequential' => env('SRI_DOCUMENT_SEQUENCE_SEEDER', 50), // Starting point for new sequences; adjust as needed
                        ],
                    );

                    $this->tallyModelChange($sequence, $created, $updated);
                }
            }
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
