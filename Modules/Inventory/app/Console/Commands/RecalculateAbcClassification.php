<?php

declare(strict_types=1);

namespace Modules\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Company;
use Modules\Inventory\Enums\AbcClassificationEnum;
use Modules\Inventory\Models\Product;

final class RecalculateAbcClassification extends Command
{
    protected $signature = 'inventory:abc-recalculate {--company= : Company ID to process (default: all)}';

    protected $description = 'Recalculate ABC classification for products based on annual sales value.';

    public function handle(): int
    {
        $companyId = $this->option('company');

        $companies = $companyId !== null
            ? Company::where('id', $companyId)->get(['id'])
            : Company::all(['id']);

        foreach ($companies as $company) {
            $this->processCompany((int) $company->id);
        }

        $this->info('ABC classification recalculated successfully.');

        return self::SUCCESS;
    }

    private function processCompany(int $companyId): void
    {
        $this->line("Processing company #{$companyId}...");

        $cutoff = now()->subYear()->toDateString();

        $annualValues = DB::table('inv_movements')
            ->join('inv_document_types', 'inv_movements.document_type_id', '=', 'inv_document_types.id')
            ->where('inv_movements.company_id', $companyId)
            ->whereNull('inv_movements.voided_at')
            ->where('inv_document_types.movement_type', 'out')
            ->where('inv_movements.movement_date', '>=', $cutoff)
            ->groupBy('inv_movements.product_id')
            ->select([
                'inv_movements.product_id',
                DB::raw('SUM(inv_movements.quantity * inv_movements.unit_cost) as annual_value'),
            ])
            ->pluck('annual_value', 'product_id');

        $totalValue = $annualValues->sum();

        Product::where('company_id', $companyId)
            ->where('is_inventory', true)
            ->orderBy('id')
            ->each(function (Product $product) use ($annualValues, $totalValue): void {
                $annualValue = (float) ($annualValues[$product->id] ?? 0);

                $classification = $annualValue === 0.0
                    ? AbcClassificationEnum::X
                    : $this->classifyByPercentage($product->id, $annualValues, $totalValue);

                $product->updateQuietly([
                    'abc_classification' => $classification,
                    'annual_value' => $annualValue,
                    'abc_calculated_at' => now(),
                ]);
            });

        $this->line("  Company #{$companyId} done.");
    }

    /** @param Collection<int, float> $annualValues */
    private function classifyByPercentage(int $productId, $annualValues, float $totalValue): AbcClassificationEnum
    {
        if ($totalValue <= 0) {
            return AbcClassificationEnum::C;
        }

        $sorted = $annualValues->sortDesc();
        $cumulative = 0.0;

        foreach ($sorted as $id => $value) {
            $cumulative += (float) $value;
            $cumulativePct = ($cumulative / $totalValue) * 100;

            if ($id === $productId) {
                return match (true) {
                    $cumulativePct <= 80 => AbcClassificationEnum::A,
                    $cumulativePct <= 95 => AbcClassificationEnum::B,
                    default => AbcClassificationEnum::C,
                };
            }
        }

        return AbcClassificationEnum::C;
    }
}
