<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_tax_rule_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_rule_id')
                ->constrained('fin_tax_rules')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('from_amount', 20, 4)->nullable();
            $table->decimal('to_amount', 20, 4)->nullable();
            $table->decimal('rate', 7, 4)->default(0);
            $table->decimal('fixed_amount', 20, 4)->default(0);
            $table->decimal('excess_from', 20, 4)->default(0);
            $table->string('description', 150)->nullable();
            $table->timestamps();

            $table->index(['tax_rule_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_tax_rule_lines');
    }
};
