<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_tax_withholding_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('core_companies')->cascadeOnDelete();

            $table->string('type', 20); // iva|renta
            $table->string('concept', 100); // servicios|bienes|profesionales etc.
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('account', 60)->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->softDeletes();

            $table->unique(['company_id', 'type', 'concept'], 'sales_tax_withholding_rules_unique');
            $table->index(['company_id', 'type'], 'sales_twr_company_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_tax_withholding_rules');
    }
};
