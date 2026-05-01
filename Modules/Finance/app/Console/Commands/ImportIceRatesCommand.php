<?php

declare(strict_types=1);

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

final class ImportIceRatesCommand extends Command
{
    protected $signature = 'sri:import-ice {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import ICE rate matrices from bundled JSON file into fin_tax_rule_lines';

    public function handle(): int
    {
        $path = module_path('Finance', 'resources/data/ice-rates.json');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(File::get($path), associative: true);
        $categories = $data['categories'] ?? [];
        $dryRun = $this->option('dry-run');
        $totalLines = 0;

        foreach ($categories as $category) {
            $definitionCode = $category['definition_code'];
            $definition = TaxDefinition::query()->where('code', $definitionCode)->first();

            if ($definition === null) {
                $this->warn("Definition not found: {$definitionCode} — skipping.");

                continue;
            }

            if ($dryRun) {
                $lineCount = count($category['lines']);
                $this->line("Would upsert rule for {$definitionCode} ({$lineCount} lines)");
                $totalLines += $lineCount;

                continue;
            }

            $rule = TaxRule::updateOrCreate(
                ['name' => $category['rule_name']],
                [
                    'description' => $category['description'],
                    'applies_to' => TaxAppliesToEnum::Venta->value,
                    'priority' => 20,
                    'conditions' => [
                        ['field' => 'product.ice_category', 'operator' => '=', 'value' => str_replace('ICE_', '', $definitionCode)],
                    ],
                    'tax_definition_id' => $definition->id,
                    'is_active' => true,
                    'valid_from' => $data['version'].'-01-01',
                    'valid_to' => null,
                ]
            );

            DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

            $now = now();
            $lines = array_map(fn (array $l): array => array_merge($l, [
                'tax_rule_id' => $rule->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $category['lines']);

            DB::table('fin_tax_rule_lines')->insert($lines);
            $totalLines += count($lines);
        }

        if ($dryRun) {
            $this->info("Dry run: would process {$totalLines} rule lines.");
        } else {
            $this->info("ICE rates imported: {$totalLines} lines upserted.");
        }

        return self::SUCCESS;
    }
}
