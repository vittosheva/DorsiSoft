<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_chart_of_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('fin_chart_of_accounts')->nullOnDelete();

            $table->string('code', 30)->comment('Código jerárquico: 1.1.01.001');
            $table->string('name', 200);
            $table->string('type', 20)->comment('AccountTypeEnum: asset, liability, equity, income, expense');
            $table->string('nature', 10)->comment('AccountNatureEnum: debit, credit');
            $table->unsignedTinyInteger('level')->default(1)->comment('Profundidad en la jerarquía');
            $table->boolean('is_control')->default(false)->comment('Cuenta de mayor: no recibe asientos directos');
            $table->boolean('allows_entries')->default(true)->comment('Cuenta hoja: recibe asientos');
            $table->boolean('is_active')->default(true);
            $table->string('sri_code', 20)->nullable()->comment('Código SRI para formularios de declaración');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'fin_coa_company_code_unique');
            $table->index(['company_id', 'parent_id'], 'fin_coa_company_parent_idx');
            $table->index(['company_id', 'type'], 'fin_coa_company_type_idx');
            $table->index(['company_id', 'is_active'], 'fin_coa_company_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_chart_of_accounts');
    }
};
