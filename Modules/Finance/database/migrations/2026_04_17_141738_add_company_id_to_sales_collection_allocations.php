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
        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });

        DB::statement('
            UPDATE sales_collection_allocations
            SET company_id = sc.company_id
            FROM sales_collections sc
            WHERE sc.id = sales_collection_allocations.collection_id
        ');

        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id', 'collection_allocations_company_id_foreign')
                ->references('id')->on('core_companies')->cascadeOnDelete();
            $table->index(['company_id', 'collection_id', 'invoice_id'], 'collection_allocations_company_collection_invoice_idx');
            $table->index(['company_id', 'invoice_id'], 'collection_allocations_company_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales_collection_allocations', function (Blueprint $table): void {
            $table->dropIndex('collection_allocations_company_invoice_idx');
            $table->dropIndex('collection_allocations_company_collection_invoice_idx');
            $table->dropForeign('collection_allocations_company_id_foreign');
            $table->dropColumn('company_id');
        });
    }
};
