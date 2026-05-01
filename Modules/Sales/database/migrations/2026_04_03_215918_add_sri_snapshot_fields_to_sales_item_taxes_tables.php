<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\System\Enums\TaxCalculationTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::whenTableDoesntHaveColumn($table, 'tax_code', function (Blueprint $blueprint): void {
                $blueprint->string('tax_code', 10)->nullable()->after('tax_type');
            });

            Schema::whenTableDoesntHaveColumn($table, 'tax_percentage_code', function (Blueprint $blueprint): void {
                $blueprint->string('tax_percentage_code', 20)->nullable()->after('tax_code');
            });

            Schema::whenTableDoesntHaveColumn($table, 'tax_calculation_type', function (Blueprint $blueprint): void {
                $blueprint->string('tax_calculation_type', 20)->nullable()->after('tax_rate');
            });

            DB::table($table)
                ->leftJoin('fin_taxes', 'fin_taxes.id', '=', "{$table}.tax_id")
                ->select([
                    "{$table}.id",
                    "{$table}.tax_type",
                    "{$table}.tax_rate",
                    'fin_taxes.sri_code',
                    'fin_taxes.sri_percentage_code',
                    'fin_taxes.calculation_type',
                ])
                ->orderBy("{$table}.id")
                ->get()
                ->each(function (object $row) use ($table): void {
                    $taxType = mb_strtoupper((string) ($row->tax_type ?? 'IVA'));
                    $taxRate = (float) $row->tax_rate;

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'tax_code' => $row->sri_code ?: match ($taxType) {
                                'ICE' => '3',
                                'ISD' => '6',
                                default => '2',
                            },
                            'tax_percentage_code' => $row->sri_percentage_code ?: ($taxType === 'IVA'
                                ? match (true) {
                                    $taxRate === 0.0 => '0',
                                    $taxRate === 5.0 => '6',
                                    $taxRate === 8.0 => '8',
                                    $taxRate === 12.0 => '2',
                                    $taxRate === 13.0, $taxRate === 14.0 => '3',
                                    $taxRate === 15.0 => '4',
                                    default => '2',
                                }
                                : (string) (int) round($taxRate, 0)),
                            'tax_calculation_type' => $row->calculation_type ?: TaxCalculationTypeEnum::Percentage->value,
                        ]);
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn($table, 'tax_code') ? 'tax_code' : null,
                Schema::hasColumn($table, 'tax_percentage_code') ? 'tax_percentage_code' : null,
                Schema::hasColumn($table, 'tax_calculation_type') ? 'tax_calculation_type' : null,
            ]));

            if ($columnsToDrop === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columnsToDrop): void {
                $blueprint->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return [
            'sales_invoice_item_taxes',
            'sales_quotation_item_taxes',
            'sales_order_item_taxes',
            'sales_credit_note_item_taxes',
        ];
    }
};
