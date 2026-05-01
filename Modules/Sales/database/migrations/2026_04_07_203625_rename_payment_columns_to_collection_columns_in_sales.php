<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropIndex('credit_notes_company_payment_idx');
            $table->dropIndex('credit_notes_par_id_idx');
            $table->renameColumn('payment_id', 'collection_id');
            $table->renameColumn('payment_allocation_reversal_id', 'collection_allocation_reversal_id');
        });

        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->index(['company_id', 'collection_id'], 'credit_notes_company_collection_idx');
            $table->index('collection_allocation_reversal_id', 'credit_notes_car_id_idx');

            $table->foreign('collection_id', 'sales_credit_notes_collection_id_foreign')
                ->references('id')
                ->on('sales_collections')
                ->nullOnDelete();

            $table->foreign('collection_allocation_reversal_id', 'sales_credit_notes_collection_allocation_reversal_id_foreign')
                ->references('id')
                ->on('sales_collection_allocation_reversals')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropForeign('sales_credit_notes_collection_id_foreign');
            $table->dropForeign('sales_credit_notes_collection_allocation_reversal_id_foreign');
            $table->dropIndex('credit_notes_company_collection_idx');
            $table->dropIndex('credit_notes_car_id_idx');
            $table->renameColumn('collection_id', 'payment_id');
            $table->renameColumn('collection_allocation_reversal_id', 'payment_allocation_reversal_id');
        });

        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->index(['company_id', 'payment_id'], 'credit_notes_company_payment_idx');
            $table->index('payment_allocation_reversal_id', 'credit_notes_par_id_idx');

            $table->foreign('payment_id', 'sales_credit_notes_payment_id_foreign')
                ->references('id')
                ->on('sales_payments')
                ->nullOnDelete();

            $table->foreign('payment_allocation_reversal_id', 'sales_credit_notes_payment_allocation_reversal_id_foreign')
                ->references('id')
                ->on('sales_payment_allocation_reversals')
                ->nullOnDelete();
        });
    }
};
