<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();

            // Tripleta SRI: establece un contador por (empresa, estab., punto, tipo)
            $table->char('establishment_code', 3);
            $table->char('emission_point_code', 3);
            $table->string('document_type', 20); // SriDocumentTypeEnum: invoice|credit_note|debit_note

            // Último secuencial emitido para esta combinación
            $table->unsignedInteger('last_sequential')->default(0);

            $table->timestamps();

            // Un único contador por combinación
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'document_type'],
                'uq_doc_sequence'
            );

            $table->index(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_document_sequences');
    }
};
