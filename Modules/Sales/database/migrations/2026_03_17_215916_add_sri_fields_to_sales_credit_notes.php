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
            // SRI sequential numbering (alongside internal 'code')
            $table->char('establishment_code', 3)->nullable()->after('code');
            $table->char('emission_point_code', 3)->nullable()->after('establishment_code');
            $table->char('sequential_number', 9)->nullable()->after('emission_point_code');

            // External invoice reference (for invoices from other systems)
            $table->string('ext_invoice_code', 17)->nullable()->after('invoice_id');
            $table->date('ext_invoice_date')->nullable()->after('ext_invoice_code');
            $table->string('ext_invoice_auth_number', 49)->nullable()->after('ext_invoice_date');

            // SRI payment methods for XML <pagos> section
            $table->json('sri_payments')->nullable()->after('notes');

            // Additional key-value info for XML <infoAdicional> section
            $table->json('additional_info')->nullable()->after('sri_payments');
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropColumn([
                'establishment_code',
                'emission_point_code',
                'sequential_number',
                'ext_invoice_code',
                'ext_invoice_date',
                'ext_invoice_auth_number',
                'sri_payments',
                'additional_info',
            ]);
        });
    }
};
