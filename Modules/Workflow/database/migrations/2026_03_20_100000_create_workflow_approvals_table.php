<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();

            // Identifica qué flujo y qué paso dentro del flujo
            $table->string('flow_key', 100);
            $table->string('step', 100);

            // Relación polimórfica con el documento aprobable
            $table->morphs('approvable');

            // El usuario que toma la decisión
            $table->foreignId('approver_id')->constrained('core_users')->cascadeOnDelete();

            // La decisión: approved | rejected
            $table->string('decision', 20);

            $table->text('notes')->nullable();
            $table->timestamp('decided_at');

            $table->timestamps();
            $table->softDeletes(); // Permite resetear sin perder auditoría

            // Índices con patrón del proyecto: company_id primero, deleted_at último
            $table->index(['company_id', 'approvable_type', 'approvable_id', 'deleted_at'], 'wfa_company_morph_deleted_idx');
            $table->index(['company_id', 'flow_key', 'deleted_at'], 'wfa_company_key_deleted_idx');
            $table->index(['company_id', 'approver_id', 'deleted_at'], 'wfa_company_approver_deleted_idx');
        });

        // Partial unique index (PostgreSQL): un aprobador solo puede tener UN registro activo
        // por (approvable, flow_key, step). El WHERE deleted_at IS NULL garantiza que
        // los registros soft-deleted (resets) no bloqueen nuevas aprobaciones.
        DB::statement('
            CREATE UNIQUE INDEX wfa_unique_active_per_approver
            ON workflow_approvals (approvable_type, approvable_id, flow_key, step, approver_id)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
    }
};
