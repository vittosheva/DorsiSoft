<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_account_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('fin_chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fin_fiscal_periods')->restrictOnDelete();

            $table->decimal('opening_balance', 15, 4)->default(0)->comment('Saldo inicial del período');
            $table->decimal('period_debit', 15, 4)->default(0)->comment('Suma de débitos en el período');
            $table->decimal('period_credit', 15, 4)->default(0)->comment('Suma de créditos en el período');
            $table->decimal('closing_balance', 15, 4)->default(0)->comment('Saldo al cierre = opening + movimiento neto');

            $table->timestamps();

            $table->unique(
                ['account_id', 'fiscal_period_id'],
                'fin_ab_account_period_unique'
            );
            $table->index(['company_id', 'fiscal_period_id'], 'fin_ab_company_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_account_balances');
    }
};
