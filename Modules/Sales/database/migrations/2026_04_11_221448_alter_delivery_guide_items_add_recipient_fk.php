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
        // Truncate before restructuring — no production data exists yet
        DB::table('sales_delivery_guide_items')->truncate();

        Schema::table('sales_delivery_guide_items', function (Blueprint $table): void {
            $table->dropForeign(['delivery_guide_id']);
            $table->dropColumn('delivery_guide_id');

            $table->foreignId('delivery_guide_recipient_id')
                ->after('id')
                ->constrained('sales_delivery_guide_recipients')
                ->cascadeOnDelete();

            $table->index('delivery_guide_recipient_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_delivery_guide_items', function (Blueprint $table): void {
            $table->dropForeign(['delivery_guide_recipient_id']);
            $table->dropIndex(['delivery_guide_recipient_id']);
            $table->dropColumn('delivery_guide_recipient_id');

            $table->foreignId('delivery_guide_id')->constrained('sales_delivery_guides')->cascadeOnDelete();
            $table->index('delivery_guide_id');
        });
    }
};
