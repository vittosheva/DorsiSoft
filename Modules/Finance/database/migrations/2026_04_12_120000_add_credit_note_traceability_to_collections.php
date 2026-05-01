<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_collections', function (Blueprint $table): void {
            $table->foreignId('credit_note_id')
                ->nullable()
                ->after('business_partner_id')
                ->constrained('sales_credit_notes')
                ->nullOnDelete();
        });

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->foreignId('credit_note_id')
                ->nullable()
                ->after('collection_id')
                ->constrained('sales_credit_notes')
                ->nullOnDelete();

            $table->foreignId('origin_invoice_id')
                ->nullable()
                ->after('credit_note_id')
                ->constrained('sales_invoices')
                ->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX sales_collections_credit_note_unique ON sales_collections (credit_note_id) WHERE credit_note_id IS NOT NULL');
        DB::statement('CREATE INDEX sales_collection_allocations_credit_note_idx ON sales_collection_allocations (credit_note_id)');
        DB::statement('CREATE INDEX sales_collection_allocations_origin_invoice_idx ON sales_collection_allocations (origin_invoice_id)');
        DB::statement('CREATE INDEX sales_collection_allocations_credit_note_invoice_idx ON sales_collection_allocations (credit_note_id, invoice_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales_collection_allocations_credit_note_invoice_idx');
        DB::statement('DROP INDEX IF EXISTS sales_collection_allocations_origin_invoice_idx');
        DB::statement('DROP INDEX IF EXISTS sales_collection_allocations_credit_note_idx');
        DB::statement('DROP INDEX IF EXISTS sales_collections_credit_note_unique');

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('origin_invoice_id');
            $table->dropConstrainedForeignId('credit_note_id');
        });

        Schema::table('sales_collections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('credit_note_id');
        });
    }
};
