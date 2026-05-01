<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_delivery_guides', function (Blueprint $table): void {
            $table->string('carrier_driver_name', 200)
                ->nullable()
                ->after('carrier_plate');
        });
    }

    public function down(): void
    {
        Schema::table('sales_delivery_guides', function (Blueprint $table): void {
            $table->dropColumn('carrier_driver_name');
        });
    }
};
