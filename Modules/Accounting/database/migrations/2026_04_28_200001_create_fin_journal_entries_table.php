<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fin_fiscal_periods')->restrictOnDelete();

            $table->string('reference', 100)->comment('JE-2024-000001');
            $table->string('description', 500);
            $table->date('entry_date');
            $table->string('status', 20)->default('draft')->comment('JournalEntryStatusEnum: draft, approved, voided');

            // Origen polimórfico
            $table->string('source_type', 60)->nullable()->comment('Ej: sales_invoice, finance_collection');
            $table->unsignedBigInteger('source_id')->nullable();

            // Totales (redundantes para validación de partida doble)
            $table->decimal('total_debit', 15, 4)->default(0);
            $table->decimal('total_credit', 15, 4)->default(0);

            // Aprobación
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('core_users')->nullOnDelete();

            // Anulación
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->text('void_reason')->nullable();

            // Reversión (asiento inverso generado automáticamente)
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('fin_journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'reference'], 'fin_je_company_reference_unique');
            $table->index(['company_id', 'status', 'deleted_at'], 'fin_je_company_status_idx');
            $table->index(['company_id', 'entry_date'], 'fin_je_company_date_idx');
            $table->index(['company_id', 'fiscal_period_id', 'status'], 'fin_je_company_period_status_idx');
            $table->index(['source_type', 'source_id'], 'fin_je_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_journal_entries');
    }
};
