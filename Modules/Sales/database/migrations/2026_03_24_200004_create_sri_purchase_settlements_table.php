<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_purchase_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);
            $table->foreignId('supplier_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot del proveedor
            $table->string('supplier_name', 200)->nullable();
            $table->string('supplier_identification_type', 20)->nullable();
            $table->string('supplier_identification', 30)->nullable();
            $table->string('supplier_address', 300)->nullable();
            $table->string('supplier_email', 150)->nullable();

            // Secuencia SRI
            $table->char('establishment_code', 3)->nullable();
            $table->char('emission_point_code', 3)->nullable();
            $table->char('sequential_number', 9)->nullable();
            $table->char('access_key', 49)->nullable();

            // Estado electrónico
            $table->string('electronic_status', 20)->nullable();

            // Datos del documento
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();
            $table->text('notes')->nullable();

            // Totales
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_base', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            $table->timestamp('electronic_submitted_at')->nullable();
            $table->timestamp('electronic_authorized_at')->nullable();
            $table->json('sri_payments')->nullable();
            $table->json('additional_info')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sri_ps_company_code_unique');
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_purchase_settlement_sri_seq'
            );
            $table->index(['company_id', 'status', 'deleted_at'], 'sri_ps_company_status_idx');
            $table->index(['company_id', 'electronic_status', 'deleted_at'], 'sri_ps_elec_status_idx');
            $table->index(['company_id', 'issue_date', 'deleted_at'], 'sri_ps_company_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_purchase_settlements');
    }
};
