<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_document_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('sri_code', 10)->nullable()->comment('Código SRI: 01=Factura, 04=LiqCompra, 05=ND, 06=NC, 07=Retención');

            // Comportamientos financieros
            $table->boolean('generates_receivable')->default(false)->comment('Genera cuenta por cobrar (CxC)');
            $table->boolean('generates_payable')->default(false)->comment('Genera cuenta por pagar (CxP)');
            $table->boolean('affects_inventory')->default(false)->comment('Afecta kardex de inventario');
            $table->boolean('affects_accounting')->default(false)->comment('Genera asiento contable automático');
            $table->boolean('requires_authorization')->default(false)->comment('Requiere autorización SRI');
            $table->boolean('allows_credit')->default(false)->comment('Permite condiciones de crédito');
            $table->boolean('is_electronic')->default(false)->comment('Comprobante electrónico SRI');
            $table->boolean('is_purchase')->default(false)->comment('Documento de compra (origen externo)');

            // Cuentas contables predeterminadas (código del plan de cuentas)
            $table->string('default_debit_account_code', 30)->nullable();
            $table->string('default_credit_account_code', 30)->nullable();

            // Flags extensibles sin necesidad de nuevas migraciones
            $table->json('behavior_flags')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'sys_doc_types_company_code_unique');
            $table->index(['company_id', 'is_active'], 'sys_doc_types_company_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_document_types');
    }
};
