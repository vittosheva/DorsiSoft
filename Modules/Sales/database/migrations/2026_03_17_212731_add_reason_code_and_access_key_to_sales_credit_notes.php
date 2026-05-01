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
            // SRI catalog reason code (01-05 per NAC-DGERCGC14-00157).
            $table->string('reason_code', 2)->nullable()->after('reason');

            // SRI 49-digit access key (clave de acceso) for electronic billing.
            $table->string('access_key', 49)->nullable()->after('reason_code');
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropColumn(['reason_code', 'access_key']);
        });
    }
};
