<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\WithholdingStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_withholdings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();

            // Snapshot del proveedor al momento de emisión
            $table->string('supplier_name', 200)->nullable();
            $table->string('supplier_identification_type', 20)->nullable();
            $table->string('supplier_identification', 30)->nullable();
            $table->string('supplier_address', 300)->nullable();

            // Secuencia SRI
            $table->char('establishment_code', 3)->nullable();
            $table->char('emission_point_code', 3)->nullable();
            $table->char('sequential_number', 9)->nullable();
            $table->char('access_key', 49)->nullable();

            // Estado del proceso electrónico
            $table->string('electronic_status', 35)->nullable();

            // Datos del comprobante
            $table->string('period_fiscal', 7)->nullable(); // YYYY/MM
            $table->string('status', 35)->default(WithholdingStatusEnum::Draft->value);
            $table->date('issue_date');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();
            $table->string('notes', 500)->nullable();

            $table->string('source_document_type', 20)->nullable();
            $table->string('source_document_number', 17)->nullable();
            $table->date('source_document_date')->nullable();

            // Campos SRI adicionales
            $table->timestamp('electronic_submitted_at')->nullable();
            $table->timestamp('electronic_authorized_at')->nullable();
            $table->json('additional_info')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_withholdings_company_code_unique');
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_sales_withholdings_sri_seq'
            );
            $table->index(['company_id', 'status', 'deleted_at'], 'sales_wh_company_status_idx');
            $table->index(['company_id', 'electronic_status', 'deleted_at'], 'sales_wh_elec_status_idx');
            $table->index(['company_id', 'issue_date', 'deleted_at'], 'sales_wh_company_date_idx');
            $table->index(['company_id', 'source_document_number', 'deleted_at'], 'sw_company_source_doc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_withholdings');
    }
};
