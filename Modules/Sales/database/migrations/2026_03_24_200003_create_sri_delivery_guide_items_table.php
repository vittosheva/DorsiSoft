<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_delivery_guide_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_guide_id')->constrained('sales_delivery_guides')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();

            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 300);
            $table->decimal('quantity', 14, 6);
            $table->integer('sort_order')->default(0);
            $table->string('description', 500)->nullable();

            $table->timestamps();

            $table->index('delivery_guide_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_guide_items');
    }
};
