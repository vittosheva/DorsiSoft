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
        Schema::table('core_establishments', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('core_companies')
                ->cascadeOnDelete();
        });

        // Poblar company_id desde la tabla pivot existente (sintaxis PostgreSQL)
        DB::statement('
            UPDATE core_establishments e
            SET company_id = ce.company_id
            FROM core_company_establishment ce
            WHERE ce.establishment_id = e.id
              AND e.company_id IS NULL
        ');

        Schema::table('core_establishments', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable(false)->change();

            // Reemplazar índices simples por compuestos con company_id primero
            $table->dropIndex(['is_active']);
            $table->dropIndex(['code']);

            $table->index(['company_id', 'is_active', 'deleted_at'], 'idx_establishments_company_active');
            $table->index(['company_id', 'code', 'deleted_at'], 'idx_establishments_company_code');
        });
    }

    public function down(): void
    {
        Schema::table('core_establishments', function (Blueprint $table): void {
            $table->dropIndex('idx_establishments_company_active');
            $table->dropIndex('idx_establishments_company_code');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            $table->index(['is_active']);
            $table->index(['code']);
        });
    }
};
