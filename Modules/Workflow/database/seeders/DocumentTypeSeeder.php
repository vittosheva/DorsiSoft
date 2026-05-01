<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Workflow\Models\DocumentType;

final class DocumentTypeSeeder extends Seeder
{
    /**
     * Canonical workflow document types.
     * These IDs are stable — ApprovalFlowSeeder references them by code.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'sales', 'name' => 'Sales', 'is_active' => true],
            ['code' => 'purchase', 'name' => 'Purchase', 'is_active' => true],
            ['code' => 'finance', 'name' => 'Finance', 'is_active' => true],
            ['code' => 'inventory', 'name' => 'Inventory', 'is_active' => true],
            ['code' => 'general', 'name' => 'General', 'is_active' => true],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['code' => $type['code']], $type);
        }

        $this->command->info('DocumentTypeSeeder: seeded '.count($types).' document types.');
    }
}
