<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreignId('invoice_id')
                ->nullable()
                ->change()
                ->constrained('sales_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreignId('invoice_id')
                ->nullable(false)
                ->change()
                ->constrained('sales_invoices')
                ->cascadeOnDelete();
        });
    }
};
