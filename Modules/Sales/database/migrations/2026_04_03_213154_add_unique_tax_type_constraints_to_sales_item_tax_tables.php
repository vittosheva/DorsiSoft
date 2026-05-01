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
        foreach ($this->tables() as $table => $itemForeignKey) {
            $duplicate = DB::table($table)
                ->select([$itemForeignKey, 'tax_type', DB::raw('COUNT(*) as total')])
                ->whereNotNull('tax_type')
                ->groupBy($itemForeignKey, 'tax_type')
                ->havingRaw('COUNT(*) > 1')
                ->first();

            if ($duplicate !== null) {
                throw new RuntimeException(
                    "Cannot add unique tax-type constraint to [{$table}]. "
                        ."Duplicate rows were found for {$itemForeignKey}={$duplicate->{$itemForeignKey}} and tax_type={$duplicate->tax_type}. "
                        .'Clean the historical data first and rerun the migration.'
                );
            }

            Schema::table($table, function (Blueprint $blueprint) use ($itemForeignKey, $table): void {
                $blueprint->unique([$itemForeignKey, 'tax_type'], $this->uniqueIndexName($table));
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables()) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique($this->uniqueIndexName($table));
            });
        }
    }

    /**
     * @return array<string, string>
     */
    private function tables(): array
    {
        return [
            'sales_invoice_item_taxes' => 'invoice_item_id',
            'sales_quotation_item_taxes' => 'quotation_item_id',
            'sales_order_item_taxes' => 'order_item_id',
            'sales_credit_note_item_taxes' => 'credit_note_item_id',
        ];
    }

    private function uniqueIndexName(string $table): string
    {
        return "{$table}_item_tax_type_unique";
    }
};
