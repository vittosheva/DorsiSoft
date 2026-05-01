<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payment_allocation_reversals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained('sales_payments')->cascadeOnDelete();
            $table->foreignId('payment_allocation_id')->constrained('sales_payment_allocations')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();

            $table->decimal('reversed_amount', 20, 4);
            $table->string('reversal_type', 20);
            $table->string('reason', 500);
            $table->timestamp('reversed_at');
            $table->foreignId('reversed_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['payment_id', 'reversed_at'], 'payment_reversals_payment_reversed_at_idx');
            $table->index(['invoice_id', 'reversed_at'], 'payment_reversals_invoice_reversed_at_idx');
            $table->index(['payment_allocation_id', 'reversal_type'], 'payment_reversals_allocation_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payment_allocation_reversals');
    }
};
