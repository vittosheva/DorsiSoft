<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_delivery_guide_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_guide_id')->constrained('sales_delivery_guides')->cascadeOnDelete();
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Recipient snapshot
            $table->string('recipient_name', 200)->nullable();
            $table->string('recipient_identification_type', 20)->nullable();
            $table->string('recipient_identification', 30)->nullable();

            // Delivery details
            $table->string('destination_address', 300)->nullable();
            $table->string('transfer_reason', 50)->nullable();
            $table->string('route', 300)->nullable();
            $table->string('customs_doc', 100)->nullable();
            $table->char('destination_establishment_code', 3)->nullable();

            // Linked invoice (optional)
            $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();

            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('delivery_guide_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_guide_recipients');
    }
};
