<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_partner_addresses', function (Blueprint $table): void {
            $table->dropIndex('idx_partner_addresses_bp_default');
            $table->index(
                ['business_partner_id', 'is_default', 'is_active', 'deleted_at'],
                'idx_partner_addresses_bp_default',
            );
        });
    }

    public function down(): void
    {
        Schema::table('core_partner_addresses', function (Blueprint $table): void {
            $table->dropIndex('idx_partner_addresses_bp_default');
            $table->index(
                ['business_partner_id', 'is_default', 'is_active'],
                'idx_partner_addresses_bp_default',
            );
        });
    }
};
