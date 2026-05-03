<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Enums\DebitNoteStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_debit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 30);

            // Factura de referencia (según SRI, la ND debe referenciar un comprobante original)
            $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();

            $table->date('issue_date');

            // Cliente
            $table->foreignId('business_partner_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_trade_name', 200)->nullable();
            $table->string('customer_identification_type', 50)->nullable();
            $table->string('customer_identification', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->json('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();

            // Moneda
            $table->char('currency_code', 3)->default('USD');

            // Motivos de la ND: [{reason: "...", value: "0.00"}, ...]
            // SRI formato XML: <motivos><motivo><razon>...</razon><valor>...</valor></motivo></motivos>
            $table->json('motivos')->default('[]');

            // Impuesto aplicable (único a nivel documento, a diferencia de facturas/NC que tienen impuestos por línea)
            $table->foreignId('tax_id')->nullable()->constrained('fin_taxes')->nullOnDelete();
            $table->string('tax_name', 100)->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();

            // Totales calculados desde motivos
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);

            // Estado
            $table->string('status', 35)->default(DebitNoteStatusEnum::Draft->value);

            // Secuencia SRI
            $table->char('access_key', 49)->nullable();
            $table->char('establishment_code', 3)->nullable();
            $table->char('emission_point_code', 3)->nullable();
            $table->char('sequential_number', 9)->nullable();

            // Referencia a factura externa (emitida fuera del sistema)
            $table->string('ext_invoice_code', 17)->nullable();
            $table->date('ext_invoice_date')->nullable();
            $table->string('ext_invoice_auth_number', 49)->nullable();

            // Forma de pago (único método para ND, a diferencia de facturas)
            $table->string('payment_method', 5)->nullable();
            $table->decimal('payment_amount', 20, 4)->nullable();

            // Información adicional XML <infoAdicional>
            $table->json('additional_info')->nullable();

            // Notas internas (no van al XML)
            $table->text('notes')->nullable();

            // Ciclo de vida
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Userstamps
            $table->foreignId('created_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('core_users')->nullOnDelete();

            // Índices
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status', 'deleted_at']);
            $table->index(['company_id', 'business_partner_id', 'deleted_at']);

            // Unicidad del secuencial SRI (MySQL permite múltiples NULLs en UNIQUE)
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_debit_note_sri_seq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_debit_notes');
    }
};
