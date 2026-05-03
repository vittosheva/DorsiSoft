<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_delivery_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            $table->date('issue_date');

            // Transportista
            $table->foreignId('carrier_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('carrier_name', 200)->nullable();
            $table->string('carrier_identification', 30)->nullable();
            $table->string('carrier_plate', 20)->nullable();

            // Destinatario
            $table->foreignId('recipient_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('recipient_name', 200)->nullable();
            $table->string('recipient_identification_type', 20)->nullable();
            $table->string('recipient_identification', 30)->nullable();
            $table->string('recipient_address', 300)->nullable();

            // Secuencia SRI
            $table->char('establishment_code', 3)->nullable();
            $table->char('emission_point_code', 3)->nullable();
            $table->char('sequential_number', 9)->nullable();
            $table->char('access_key', 49)->nullable();

            // Estado electrónico
            $table->string('electronic_status', 35)->nullable();

            // Datos del traslado
            $table->string('status', 35)->default(DeliveryGuideStatusEnum::Draft->value);
            $table->date('transport_date');
            $table->string('origin_address', 300)->nullable();
            $table->string('destination_address', 300)->nullable();
            $table->string('route', 300)->nullable();
            $table->string('transport_reason', 300)->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 500)->nullable();
            $table->string('notes', 500)->nullable();

            $table->timestamp('electronic_submitted_at')->nullable();
            $table->timestamp('electronic_authorized_at')->nullable();
            $table->json('additional_info')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sales_delivery_guides_company_code_unique');
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_delivery_guide_sales_seq'
            );
            $table->index(['company_id', 'status', 'deleted_at'], 'sales_dg_company_status_idx');
            $table->index(['company_id', 'electronic_status', 'deleted_at'], 'sales_dg_elec_status_idx');
            $table->index(['company_id', 'transport_date', 'deleted_at'], 'sales_dg_company_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_guides');
    }
};
