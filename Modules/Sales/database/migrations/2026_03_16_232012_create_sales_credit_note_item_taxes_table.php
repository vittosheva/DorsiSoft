<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_credit_note_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_item_id')->constrained('sales_credit_note_items')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();
            $table->string('tax_name', 100);
            $table->string('tax_type', 30)->nullable();
            $table->decimal('tax_rate', 20, 4)->default(0);
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_note_item_taxes');
    }
};
