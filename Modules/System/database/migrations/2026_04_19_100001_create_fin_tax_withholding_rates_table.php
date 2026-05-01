<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_tax_withholding_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_definition_id')->constrained('fin_tax_definitions')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->string('sri_code', 10);
            $table->string('description', 255)->nullable();
            $table->string('applies_to', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tax_definition_id', 'is_active'], 'fin_tax_wh_rates_def_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_tax_withholding_rates');
    }
};
