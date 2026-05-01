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
        Schema::table('core_users', function (Blueprint $table): void {
            $table->foreignId('business_partner_id')
                ->nullable()
                ->after('company_id')
                ->constrained('core_business_partners')
                ->nullOnDelete();

            $table->index('business_partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_users', function (Blueprint $table): void {
            $table->dropForeign(['business_partner_id']);
            $table->dropIndex(['business_partner_id']);
            $table->dropColumn('business_partner_id');
        });
    }
};
