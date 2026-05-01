<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_tax_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('core_companies')
                ->cascadeOnDelete();
            $table->string('applicable_type', 100);
            $table->unsignedBigInteger('applicable_id');
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('fin_taxes')
                ->nullOnDelete();
            $table->foreignId('tax_definition_id')
                ->nullable()
                ->constrained('fin_tax_definitions')
                ->nullOnDelete();
            $table->string('tax_type', 20);
            $table->string('sri_code', 10)->nullable();
            $table->string('sri_percentage_code', 10)->nullable();
            $table->decimal('base_amount', 20, 4);
            $table->decimal('rate', 7, 4);
            $table->decimal('tax_amount', 20, 4);
            $table->string('calculation_type', 20);
            $table->json('snapshot');
            $table->date('applied_at');
            $table->timestamps();
            $table->userstamps();

            $table->index(['company_id', 'applicable_type', 'applicable_id']);
            $table->index(['company_id', 'tax_type', 'applied_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_tax_applications');
    }
};
