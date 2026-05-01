<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_withholding_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('withholding_id')->constrained('sales_withholdings')->cascadeOnDelete();

            // Tipo de impuesto retenido
            $table->string('tax_type', 10); // 'IR' or 'IVA'
            $table->string('tax_code', 10); // Código SRI de retención
            $table->decimal('tax_rate', 8, 4);
            $table->decimal('base_amount', 20, 4);
            $table->decimal('withheld_amount', 20, 4);

            // Referencia al comprobante origen
            $table->string('source_document_type', 5)->nullable(); // '01', '03', etc.
            $table->string('source_document_number', 17)->nullable(); // 001-001-000000001
            $table->date('source_document_date')->nullable();

            $table->timestamps();

            $table->index('withholding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_withholding_items');
    }
};
