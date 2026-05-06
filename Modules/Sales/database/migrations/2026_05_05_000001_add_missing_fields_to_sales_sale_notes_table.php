<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_sale_notes', function (Blueprint $table): void {
            $table->foreignId('document_type_id')->nullable()->after('company_id')->constrained('sys_document_types')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('seller_id')->constrained('inv_warehouses')->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->after('warehouse_id')->constrained('sales_price_lists')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('core_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_sale_notes', function (Blueprint $table): void {
            $table->dropForeignKeyIfExists(['document_type_id']);
            $table->dropForeignKeyIfExists(['warehouse_id']);
            $table->dropForeignKeyIfExists(['price_list_id']);
            $table->dropForeignKeyIfExists(['deleted_by']);
            $table->dropColumn(['document_type_id', 'warehouse_id', 'price_list_id', 'deleted_by']);
        });
    }
};
