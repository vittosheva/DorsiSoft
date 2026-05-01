<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_withholding_items', function (Blueprint $table): void {
            $table->foreignId('withholding_rate_id')
                ->nullable()
                ->after('withholding_id')
                ->constrained('fin_tax_withholding_rates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_withholding_items', function (Blueprint $table): void {
            $table->dropForeign(['withholding_rate_id']);
            $table->dropColumn('withholding_rate_id');
        });
    }
};
