<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_emission_points', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('core_companies')
                ->cascadeOnDelete();
        });

        // Poblar company_id desde el establecimiento padre (sintaxis PostgreSQL)
        DB::statement('
            UPDATE core_emission_points ep
            SET company_id = e.company_id
            FROM core_establishments e
            WHERE e.id = ep.establishment_id
              AND ep.company_id IS NULL
        ');

        Schema::table('core_emission_points', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable(false)->change();

            // Índice único ahora incluye company_id
            $table->dropUnique(['establishment_id', 'code']);
            $table->dropIndex(['establishment_id', 'is_active']);
            $table->dropIndex('idx_emission_points_estab_default');

            $table->unique(['company_id', 'establishment_id', 'code'], 'uq_emission_points_company_estab_code');
            $table->index(['company_id', 'establishment_id', 'is_active', 'deleted_at'], 'idx_emission_points_company_active');
            $table->index(['company_id', 'establishment_id', 'is_default', 'deleted_at'], 'idx_emission_points_company_default');
        });
    }

    public function down(): void
    {
        Schema::table('core_emission_points', function (Blueprint $table): void {
            $table->dropUnique('uq_emission_points_company_estab_code');
            $table->dropIndex('idx_emission_points_company_active');
            $table->dropIndex('idx_emission_points_company_default');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            $table->unique(['establishment_id', 'code']);
            $table->index(['establishment_id', 'is_active']);
            $table->index(['establishment_id', 'is_default', 'deleted_at'], 'idx_emission_points_estab_default');
        });
    }
};
