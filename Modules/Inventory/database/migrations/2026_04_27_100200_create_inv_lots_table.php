<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('inv_products')->restrictOnDelete();
            $table->string('code', 100);
            $table->date('expiry_date')->nullable();
            $table->date('manufactured_date')->nullable();
            $table->string('supplier_lot_code', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();

            $table->unique(['company_id', 'product_id', 'code'], 'inv_lots_company_product_code_unique');
            $table->index(['company_id', 'product_id', 'is_active'], 'inv_lots_company_product_active_index');
            $table->index(['company_id', 'expiry_date'], 'inv_lots_company_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_lots');
    }
};
