<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_products', function (Blueprint $table): void {
            $table->boolean('tracks_lots')->default(false)->after('is_inventory');
            $table->boolean('tracks_serials')->default(false)->after('tracks_lots');
        });
    }

    public function down(): void
    {
        Schema::table('inv_products', function (Blueprint $table): void {
            $table->dropColumn(['tracks_lots', 'tracks_serials']);
        });
    }
};
