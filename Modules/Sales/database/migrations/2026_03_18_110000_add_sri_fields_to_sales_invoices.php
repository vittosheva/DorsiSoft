<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            // Secuencia SRI: establece el número oficial del comprobante
            $table->char('establishment_code', 3)->nullable()->after('credited_amount');
            $table->char('emission_point_code', 3)->nullable()->after('establishment_code');
            $table->char('sequential_number', 9)->nullable()->after('emission_point_code');
            $table->char('access_key', 49)->nullable()->after('sequential_number');

            // Sección <pagos> del XML SRI
            $table->json('sri_payments')->nullable()->after('access_key');

            // Sección <infoAdicional> del XML SRI
            $table->json('additional_info')->nullable()->after('sri_payments');

            // Unicidad del secuencial SRI por empresa
            // MySQL permite múltiples NULLs en columnas con UNIQUE, por lo que los borradores (sin secuencial) no colisionan
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_invoice_sri_seq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropUnique('uq_invoice_sri_seq');
            $table->dropColumn([
                'establishment_code',
                'emission_point_code',
                'sequential_number',
                'access_key',
                'sri_payments',
                'additional_info',
            ]);
        });
    }
};
