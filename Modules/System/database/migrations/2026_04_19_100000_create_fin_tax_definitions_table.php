<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\System\Enums\TaxAppliesToEnum;
use Modules\System\Enums\TaxBaseTypeEnum;
use Modules\System\Enums\TaxCalculationTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_tax_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('tax_group', 20);
            $table->string('tax_type', 20);
            $table->string('applies_to', 20)->default(TaxAppliesToEnum::Venta->value);
            $table->decimal('rate', 10, 4)->nullable();
            $table->decimal('fixed_amount', 10, 4)->nullable();
            $table->string('calculation_type', 20)->default(TaxCalculationTypeEnum::Percentage->value);
            $table->string('base_type', 20)->default(TaxBaseTypeEnum::Precio->value);
            $table->boolean('is_exempt')->default(false);
            $table->boolean('is_zero_rate')->default(false);
            $table->boolean('is_withholding')->default(false);
            $table->string('sri_code', 10)->nullable();
            $table->string('sri_percentage_code', 10)->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tax_group', 'is_active'], 'fin_tax_defs_group_active_index');
            $table->index(['sri_code', 'sri_percentage_code'], 'fin_tax_defs_sri_codes_index');
            $table->index('is_active', 'fin_tax_defs_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_tax_definitions');
    }
};
