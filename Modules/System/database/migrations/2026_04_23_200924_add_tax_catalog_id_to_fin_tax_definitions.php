<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_tax_definitions', function (Blueprint $table): void {
            $table->foreignId('tax_catalog_id')
                ->nullable()
                ->after('code')
                ->constrained('fin_tax_catalogs')
                ->nullOnDelete();

            $table->index(['tax_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::table('fin_tax_definitions', function (Blueprint $table): void {
            $table->dropForeign(['tax_catalog_id']);
            $table->dropIndex(['tax_catalog_id']);
            $table->dropColumn('tax_catalog_id');
        });
    }
};
