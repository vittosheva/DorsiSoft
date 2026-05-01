<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_document_sequence_history', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('document_sequence_id')
                ->constrained('sales_document_sequences')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('core_companies')
                ->cascadeOnDelete();

            $table->char('establishment_code', 3);
            $table->char('emission_point_code', 3);
            $table->string('document_type', 20);

            // 'record' | 'reset' | 'manual_correction'
            $table->string('event', 30);

            $table->unsignedInteger('previous_value');
            $table->unsignedInteger('new_value');

            // Obligatorio para event = 'reset'
            $table->string('reason')->nullable();

            // Sin FK — usuario pertenece a módulo People (cross-module)
            $table->unsignedBigInteger('performed_by')->nullable();

            $table->timestamps();

            // Índice para la consulta más frecuente: historial de una secuencia
            $table->index(
                ['company_id', 'establishment_code', 'emission_point_code', 'document_type', 'created_at'],
                'idx_seq_history_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_document_sequence_history');
    }
};
