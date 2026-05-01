<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_document_series', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('sys_document_types')->cascadeOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained('core_establishments')->nullOnDelete();

            $table->string('prefix', 20)->nullable()->comment('Ej: INT, ORD, COT');
            $table->unsignedInteger('current_sequence')->default(0);
            $table->unsignedTinyInteger('padding')->default(6)->comment('Ceros a la izquierda');
            $table->unsignedSmallInteger('reset_year')->nullable()->comment('Año del último reinicio');
            $table->boolean('auto_reset_yearly')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->userstamps();

            $table->unique(
                ['company_id', 'document_type_id', 'establishment_id', 'prefix'],
                'sys_doc_series_company_type_establishment_prefix_unique'
            );
            $table->index(['company_id', 'document_type_id'], 'sys_doc_series_company_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_document_series');
    }
};
