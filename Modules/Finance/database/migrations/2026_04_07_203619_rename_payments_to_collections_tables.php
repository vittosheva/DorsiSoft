<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_payment_allocation_reversals', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['payment_allocation_id']);
        });

        Schema::table('sales_payment_allocations', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
        });

        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['payment_allocation_reversal_id']);
        });

        Schema::rename('sales_payments', 'sales_collections');
        Schema::rename('sales_payment_allocations', 'sales_collection_allocations');
        Schema::rename('sales_payment_allocation_reversals', 'sales_collection_allocation_reversals');

        Schema::table('sales_collections', function (Blueprint $table): void {
            $table->renameColumn('payment_date', 'collection_date');
            $table->renameColumn('payment_method', 'collection_method');
            $table->renameIndex('sales_payments_company_code_unique', 'sales_collections_company_code_unique');
            $table->renameIndex('payments_company_date_deleted_idx', 'collections_company_date_deleted_idx');
            $table->renameIndex('payments_company_bp_deleted_idx', 'collections_company_bp_deleted_idx');
            $table->renameIndex('payments_company_method_deleted_idx', 'collections_company_method_deleted_idx');
        });

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->renameColumn('payment_id', 'collection_id');
            $table->renameIndex('allocations_payment_invoice_unique', 'allocations_collection_invoice_unique');
            $table->renameIndex('allocations_invoice_idx', 'collection_allocations_invoice_idx');
        });

        Schema::table('sales_collection_allocation_reversals', function (Blueprint $table): void {
            $table->renameColumn('payment_id', 'collection_id');
            $table->renameColumn('payment_allocation_id', 'collection_allocation_id');
            $table->renameIndex('payment_reversals_payment_reversed_at_idx', 'collection_reversals_collection_reversed_at_idx');
            $table->renameIndex('payment_reversals_invoice_reversed_at_idx', 'collection_reversals_invoice_reversed_at_idx');
            $table->renameIndex('payment_reversals_allocation_type_idx', 'collection_reversals_allocation_type_idx');
        });

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->foreign('collection_id', 'sales_collection_allocations_collection_id_foreign')
                ->references('id')
                ->on('sales_collections')
                ->cascadeOnDelete();
        });

        Schema::table('sales_collection_allocation_reversals', function (Blueprint $table): void {
            $table->foreign('collection_id', 'sales_collection_allocation_reversals_collection_id_foreign')
                ->references('id')
                ->on('sales_collections')
                ->cascadeOnDelete();

            $table->foreign('collection_allocation_id', 'sales_collection_allocation_reversals_collection_allocation_id_foreign')
                ->references('id')
                ->on('sales_collection_allocations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_collection_allocation_reversals', function (Blueprint $table): void {
            $table->dropForeign('sales_collection_allocation_reversals_collection_id_foreign');
            $table->dropForeign('sales_collection_allocation_reversals_collection_allocation_id_foreign');
        });

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->dropForeign('sales_collection_allocations_collection_id_foreign');
        });

        Schema::table('sales_collection_allocation_reversals', function (Blueprint $table): void {
            $table->renameColumn('collection_id', 'payment_id');
            $table->renameColumn('collection_allocation_id', 'payment_allocation_id');
            $table->renameIndex('collection_reversals_collection_reversed_at_idx', 'payment_reversals_payment_reversed_at_idx');
            $table->renameIndex('collection_reversals_invoice_reversed_at_idx', 'payment_reversals_invoice_reversed_at_idx');
            $table->renameIndex('collection_reversals_allocation_type_idx', 'payment_reversals_allocation_type_idx');
        });

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->renameColumn('collection_id', 'payment_id');
            $table->renameIndex('allocations_collection_invoice_unique', 'allocations_payment_invoice_unique');
            $table->renameIndex('collection_allocations_invoice_idx', 'allocations_invoice_idx');
        });

        Schema::table('sales_collections', function (Blueprint $table): void {
            $table->renameColumn('collection_date', 'payment_date');
            $table->renameColumn('collection_method', 'payment_method');
            $table->renameIndex('sales_collections_company_code_unique', 'sales_payments_company_code_unique');
            $table->renameIndex('collections_company_date_deleted_idx', 'payments_company_date_deleted_idx');
            $table->renameIndex('collections_company_bp_deleted_idx', 'payments_company_bp_deleted_idx');
            $table->renameIndex('collections_company_method_deleted_idx', 'payments_company_method_deleted_idx');
        });

        Schema::rename('sales_collection_allocation_reversals', 'sales_payment_allocation_reversals');
        Schema::rename('sales_collection_allocations', 'sales_payment_allocations');
        Schema::rename('sales_collections', 'sales_payments');

        Schema::table('sales_payment_allocations', function (Blueprint $table): void {
            $table->foreign('payment_id', 'sales_payment_allocations_payment_id_foreign')
                ->references('id')
                ->on('sales_payments')
                ->cascadeOnDelete();
        });

        Schema::table('sales_payment_allocation_reversals', function (Blueprint $table): void {
            $table->foreign('payment_id', 'sales_payment_allocation_reversals_payment_id_foreign')
                ->references('id')
                ->on('sales_payments')
                ->cascadeOnDelete();

            $table->foreign('payment_allocation_id', 'sales_payment_allocation_reversals_payment_allocation_id_foreign')
                ->references('id')
                ->on('sales_payment_allocations')
                ->cascadeOnDelete();
        });

        Schema::table('sales_credit_notes', function (Blueprint $table): void {
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
