<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_debit_notes', function (Blueprint $table): void {
            $table->json('sri_payments')->nullable()->after('payment_amount');
        });

        DB::table('sales_debit_notes')
            ->select(['id', 'payment_method', 'payment_amount', 'total'])
            ->whereNotNull('payment_method')
            ->orderBy('id')
            ->get()
            ->each(function (object $debitNote): void {
                $amount = number_format((float) ($debitNote->payment_amount ?? $debitNote->total ?? 0), 4, '.', '');

                DB::table('sales_debit_notes')
                    ->where('id', $debitNote->id)
                    ->update([
                        'sri_payments' => json_encode([[
                            'method' => (string) $debitNote->payment_method,
                            'amount' => $amount,
                        ]], JSON_UNESCAPED_UNICODE),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_debit_notes', function (Blueprint $table): void {
            $table->dropColumn('sri_payments');
        });
    }
};
