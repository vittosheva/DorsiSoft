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
        Schema::create('inv_product_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('inv_products')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('fin_taxes')->cascadeOnDelete();
            $table->string('tax_type', 30);
            $table->timestamps();

            $table->unique(['product_id', 'tax_id'], 'inv_product_taxes_product_tax_unique');
            $table->unique(['product_id', 'tax_type'], 'inv_product_taxes_product_type_unique');
            $table->index(['tax_id', 'tax_type'], 'inv_product_taxes_tax_type_index');
        });

        DB::table('inv_products')
            ->join('fin_taxes', 'fin_taxes.id', '=', 'inv_products.tax_id')
            ->whereNotNull('inv_products.tax_id')
            ->select([
                'inv_products.id as product_id',
                'fin_taxes.id as tax_id',
                'fin_taxes.type as tax_type',
            ])
            ->orderBy('inv_products.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('inv_product_taxes')->updateOrInsert(
                    [
                        'product_id' => $row->product_id,
                        'tax_id' => $row->tax_id,
                    ],
                    [
                        'tax_type' => $row->tax_type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_product_taxes');
    }
};
