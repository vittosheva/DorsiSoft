<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_approval_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();

            // Clave del flujo registrado en ApprovalRegistry
            $table->string('flow_key', 100);

            // Control de activación por tenant
            $table->boolean('is_enabled')->default(false);

            // Monto mínimo para activar la aprobación (null = siempre aplica)
            $table->decimal('min_amount', 15, 2)->nullable();

            // Override de roles requeridos por tenant (null = usa los definidos en el código)
            $table->json('required_roles')->nullable();

            $table->timestamps();

            // Un tenant solo puede tener una configuración por flow_key
            $table->unique(['company_id', 'flow_key'], 'was_company_flow_unique');

            // Índice para lookup rápido por tenant
            $table->index(['company_id'], 'was_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approval_settings');
    }
};
