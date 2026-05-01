<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;

final class WorkflowDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,
            ApprovalFlowSeeder::class,
        ]);
    }
}
