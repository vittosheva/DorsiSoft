<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // approval_flows: zero indexes beyond PK.
        // Every approval resolution queries this table by (company_id, document_type_id, is_active).
        Schema::table('approval_flows', function (Blueprint $table): void {
            $table->index(
                ['company_id', 'document_type_id', 'is_active'],
                'approval_flows_company_doctype_active_idx'
            );
            $table->index(
                ['company_id', 'is_active'],
                'approval_flows_company_active_idx'
            );
        });

        // approval_flow_roles: zero indexes beyond PK.
        // Joined on every approval resolution to retrieve the required roles per step.
        Schema::table('approval_flow_roles', function (Blueprint $table): void {
            $table->index(
                ['approval_flow_id', 'step'],
                'approval_flow_roles_flow_step_idx'
            );
            $table->index('role_id', 'approval_flow_roles_role_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table): void {
            $table->dropIndex('approval_flows_company_doctype_active_idx');
            $table->dropIndex('approval_flows_company_active_idx');
        });

        Schema::table('approval_flow_roles', function (Blueprint $table): void {
            $table->dropIndex('approval_flow_roles_flow_step_idx');
            $table->dropIndex('approval_flow_roles_role_id_idx');
        });
    }
};
