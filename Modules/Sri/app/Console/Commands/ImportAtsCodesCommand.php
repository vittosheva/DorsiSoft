<?php

declare(strict_types=1);

namespace Modules\Sri\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\System\Models\SriCatalog;

final class ImportAtsCodesCommand extends Command
{
    protected $signature = 'sri:import-ats {--dry-run : Show what would be imported without saving}';

    protected $description = 'Import ATS codes from bundled JSON file into sri_catalogs table';

    public function handle(): int
    {
        $path = module_path('Sri', 'resources/data/ats-codes.json');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(File::get($path), associative: true);
        $catalogs = $data['catalogs'] ?? [];

        $dryRun = $this->option('dry-run');
        $created = 0;
        $updated = 0;

        foreach ($catalogs as $catalogType => $records) {
            foreach ($records as $record) {
                $this->processRecord($catalogType, $record, $dryRun, $created, $updated);
            }
        }

        if ($dryRun) {
            $this->info("Dry run: would create {$created}, update {$updated} records.");
        } else {
            $this->info("Imported: {$created} created, {$updated} updated.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function processRecord(string $catalogType, array $record, bool $dryRun, int &$created, int &$updated): void
    {
        $attributes = [
            'catalog_type' => $catalogType,
            'code' => $record['code'],
        ];

        $values = array_merge($attributes, [
            'name' => $record['name'],
            'description' => $record['description'] ?? null,
            'is_active' => true,
        ]);

        if ($dryRun) {
            $exists = SriCatalog::query()
                ->where('catalog_type', $catalogType)
                ->where('code', $record['code'])
                ->exists();

            $exists ? $updated++ : $created++;

            return;
        }

        $model = SriCatalog::updateOrCreate($attributes, $values);
        $model->wasRecentlyCreated ? $created++ : $updated++;
    }
}
