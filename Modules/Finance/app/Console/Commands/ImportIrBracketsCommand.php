<?php

declare(strict_types=1);

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Models\TaxDefinition;
use Modules\System\Models\TaxRule;

final class ImportIrBracketsCommand extends Command
{
    protected $signature = 'sri:import-ir {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import IR progressive brackets from bundled JSON file into fin_tax_rule_lines';

    public function handle(): int
    {
        $path = module_path('Finance', 'resources/data/ir-brackets.json');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(File::get($path), associative: true);
        $brackets = $data['brackets'] ?? [];
        $definitionCode = $data['definition_code'] ?? 'IR_PN_PROGRESIVO';
        $dryRun = $this->option('dry-run');

        $definition = TaxDefinition::query()->where('code', $definitionCode)->first();

        if ($definition === null) {
            $this->error("Definition not found: {$definitionCode}. Run TaxDefinitionSeeder first.");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info("Dry run: would upsert {$data['version']} brackets for {$definitionCode} (".count($brackets).' lines).');

            return self::SUCCESS;
        }

        $rule = TaxRule::updateOrCreate(
            ['name' => "IR PN — Tabla progresiva {$data['version']}"],
            [
                'description' => $data['description'],
                'applies_to' => TaxAppliesToEnum::Compra->value,
                'priority' => 10,
                'conditions' => [
                    ['field' => 'partner.taxpayer_type', 'operator' => '=', 'value' => 'persona_natural'],
                ],
                'tax_definition_id' => $definition->id,
                'is_active' => true,
                'valid_from' => $data['version'].'-01-01',
                'valid_to' => null,
            ]
        );

        DB::table('fin_tax_rule_lines')->where('tax_rule_id', $rule->id)->delete();

        $now = now();
        $lines = array_map(fn (array $b): array => array_merge($b, [
            'tax_rule_id' => $rule->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $brackets);

        DB::table('fin_tax_rule_lines')->insert($lines);

        $this->info('IR brackets imported: '.count($lines)." lines for version {$data['version']}.");

        return self::SUCCESS;
    }
}
