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
        Schema::create('sales_debit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('sales_debit_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inv_products')->nullOnDelete();
            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('product_unit', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->text('description')->nullable();
            $table->string('detail_1', 255)->nullable();
            $table->string('detail_2', 255)->nullable();
            $table->decimal('quantity', 20, 6)->default(1);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 20, 4)->nullable();
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->timestamps();

            $table->index(['debit_note_id', 'sort_order'], 'sales_debit_note_items_debit_note_sort_idx');
        });

        Schema::create('sales_debit_note_item_taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('debit_note_item_id')->constrained('sales_debit_note_items')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();
            $table->string('tax_name', 100)->nullable();
            $table->string('tax_type', 30)->nullable();
            $table->string('tax_code', 10)->nullable();
            $table->string('tax_percentage_code', 20)->nullable();
            $table->decimal('tax_rate', 20, 4)->default(0);
            $table->string('tax_calculation_type', 20)->nullable();
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->timestamps();
        });

        $debitNotes = DB::table('sales_debit_notes')
            ->orderBy('id')
            ->get([
                'id',
                'motivos',
                'tax_id',
                'tax_name',
                'tax_rate',
                'subtotal',
                'tax_amount',
                'total',
            ]);

        foreach ($debitNotes as $debitNote) {
            $rawMotivos = $debitNote->motivos;

            if (is_string($rawMotivos)) {
                $motivos = json_decode($rawMotivos, true);
            } else {
                $motivos = $rawMotivos;
            }

            if (! is_array($motivos) || $motivos === []) {
                continue;
            }

            foreach (array_values($motivos) as $index => $motivo) {
                $reason = mb_trim((string) ($motivo['reason'] ?? ''));
                $subtotal = number_format((float) ($motivo['value'] ?? 0), 4, '.', '');

                if ((float) $subtotal <= 0) {
                    continue;
                }

                $taxAmount = number_format(((float) $subtotal * (float) ($debitNote->tax_rate ?? 0)) / 100, 4, '.', '');
                $total = number_format((float) $subtotal + (float) $taxAmount, 4, '.', '');

                $itemId = DB::table('sales_debit_note_items')->insertGetId([
                    'debit_note_id' => $debitNote->id,
                    'product_id' => null,
                    'product_code' => null,
                    'product_name' => mb_substr($reason !== '' ? $reason : 'Additional charge', 0, 255),
                    'product_unit' => null,
                    'sort_order' => $index + 1,
                    'description' => $reason !== '' ? $reason : 'Additional charge',
                    'detail_1' => null,
                    'detail_2' => null,
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => 0,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (filled($debitNote->tax_name) || filled($debitNote->tax_id)) {
                    DB::table('sales_debit_note_item_taxes')->insert([
                        'debit_note_item_id' => $itemId,
                        'tax_id' => $debitNote->tax_id,
                        'tax_name' => $debitNote->tax_name,
                        'tax_type' => 'IVA',
                        'tax_code' => null,
                        'tax_percentage_code' => null,
                        'tax_rate' => number_format((float) ($debitNote->tax_rate ?? 0), 4, '.', ''),
                        'tax_calculation_type' => 'percentage',
                        'base_amount' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_debit_note_item_taxes');
        Schema::dropIfExists('sales_debit_note_items');
    }
};
