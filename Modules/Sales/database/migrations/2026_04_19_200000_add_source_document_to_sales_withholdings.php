<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_withholdings', function (Blueprint $table): void {
            $table->foreignId('source_purchase_settlement_id')
                ->nullable()
                ->after('source_document_date')
                ->constrained('sales_purchase_settlements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_withholdings', function (Blueprint $table): void {
            $table->dropForeign(['source_purchase_settlement_id']);
            $table->dropColumn([
                'source_purchase_settlement_id',
            ]);
        });
    }
};
