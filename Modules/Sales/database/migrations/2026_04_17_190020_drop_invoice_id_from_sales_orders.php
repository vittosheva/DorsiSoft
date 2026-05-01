<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign('sales_orders_invoice_id_foreign');
            $table->dropIndex('sales_orders_invoice_id_idx');
            $table->dropColumn('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->index('invoice_id', 'sales_orders_invoice_id_idx');
        });
    }
};
