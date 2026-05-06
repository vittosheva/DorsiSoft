<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_sale_note_items', function (Blueprint $table) {
            $table->string('detail_1')->nullable()->after('description');
            $table->string('detail_2')->nullable()->after('detail_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_sale_note_items', function (Blueprint $table) {
            $table->dropColumn(['detail_1', 'detail_2']);
        });
    }
};
