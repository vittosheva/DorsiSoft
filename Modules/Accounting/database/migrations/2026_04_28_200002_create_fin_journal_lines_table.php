<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('fin_journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('fin_chart_of_accounts')->restrictOnDelete();

            $table->string('description', 300)->nullable();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);

            // Soporte multidivisa
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default('1.000000');
            $table->decimal('debit_base', 15, 4)->default(0)->comment('Monto en moneda base (USD)');
            $table->decimal('credit_base', 15, 4)->default(0);

            $table->unsignedSmallInteger('line_number')->default(1);

            $table->timestamps();

            $table->index(['journal_entry_id', 'line_number'], 'fin_jl_entry_line_idx');
            $table->index('account_id', 'fin_jl_account_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_journal_lines');
    }
};
