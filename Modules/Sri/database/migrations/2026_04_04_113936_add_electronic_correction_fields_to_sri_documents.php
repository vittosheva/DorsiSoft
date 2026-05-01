<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'sales_withholdings' => 'sales_withholdings_correction_status_idx',
            'sales_delivery_guides' => 'sales_delivery_guides_correction_status_idx',
            'sales_purchase_settlements' => 'sales_purchase_settlements_correction_status_idx',
        ];

        foreach ($tables as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->string('correction_status', 20)->nullable()->after('electronic_authorized_at');
                $table->unsignedBigInteger('correction_source_id')->nullable()->after('correction_status');
                $table->unsignedBigInteger('superseded_by_id')->nullable()->after('correction_source_id');
                $table->timestamp('correction_requested_at')->nullable()->after('superseded_by_id');
                $table->timestamp('corrected_at')->nullable()->after('correction_requested_at');
                $table->string('correction_reason', 500)->nullable()->after('corrected_at');
                $table->index(['company_id', 'correction_status', 'deleted_at'], $indexName);
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'sales_withholdings' => 'sales_withholdings_correction_status_idx',
            'sales_delivery_guides' => 'sales_delivery_guides_correction_status_idx',
            'sales_purchase_settlements' => 'sales_purchase_settlements_correction_status_idx',
        ];

        foreach ($tables as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
                $table->dropColumn([
                    'correction_status',
                    'correction_source_id',
                    'superseded_by_id',
                    'correction_requested_at',
                    'corrected_at',
                    'correction_reason',
                ]);
            });
        }
    }
};
