<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_price_lists')) {
            return;
        }

        DB::statement('CREATE UNIQUE INDEX sales_price_lists_active_default_unique ON sales_price_lists (company_id) WHERE is_default = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales_price_lists_active_default_unique');
    }
};
