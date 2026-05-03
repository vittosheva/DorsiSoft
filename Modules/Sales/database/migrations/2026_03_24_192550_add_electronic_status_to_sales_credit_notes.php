<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->string('electronic_status', 35)->nullable()->after('access_key');
            $table->timestamp('electronic_submitted_at')->nullable()->after('electronic_status');
            $table->timestamp('electronic_authorized_at')->nullable()->after('electronic_submitted_at');

            $table->index(
                ['company_id', 'electronic_status', 'deleted_at'],
                'sales_cn_elec_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropIndex('sales_cn_elec_status_idx');
            $table->dropColumn(['electronic_status', 'electronic_submitted_at', 'electronic_authorized_at']);
        });
    }
};
