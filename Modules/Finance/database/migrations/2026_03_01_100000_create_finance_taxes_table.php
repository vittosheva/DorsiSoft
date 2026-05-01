<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\System\Enums\TaxCalculationTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('type', 20);
            $table->string('sri_code', 10)->nullable()->after('type');
            $table->string('sri_percentage_code', 20)->nullable()->after('sri_code');
            $table->string('calculation_type', 20)->default(TaxCalculationTypeEnum::Percentage->value)->after('rate');
            $table->decimal('rate', 7, 4)->default(0);
            $table->string('tax_category', 20)->default('taxable');
            $table->text('description')->nullable();
            $table->string('tax_catalog_version', 10)->default(now()->format('Y'));
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'fin_taxes_company_code_unique');
            $table->unique(
                ['company_id', 'type', 'sri_code', 'sri_percentage_code'],
                'fin_taxes_unique_sri_combo'
            );
            $table->index(['company_id', 'type', 'deleted_at'], 'fin_taxes_company_type_deleted_index');
            $table->index(['company_id', 'sri_code', 'deleted_at'], 'fin_taxes_company_sri_code_deleted_index');
            $table->index(['company_id', 'is_active', 'deleted_at'], 'fin_taxes_company_active_deleted_index');
        });

        Schema::table('core_companies', function (Blueprint $table): void {
            $table->foreignId('default_tax_id')->nullable()->after('default_currency_id')->constrained('fin_taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('core_companies', function (Blueprint $table): void {
            $table->dropForeign(['default_tax_id']);
            $table->dropColumn('default_tax_id');
        });

        Schema::dropIfExists('fin_taxes');
    }
};
