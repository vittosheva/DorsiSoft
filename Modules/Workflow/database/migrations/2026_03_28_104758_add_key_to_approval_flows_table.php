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
        Schema::table('approval_flows', function (Blueprint $table) {
            // String slug key used by HasApprovals::approvalDecision() and WorkflowApprovalSetting.flow_key.
            // Nullable initially to allow populating existing rows before enforcing uniqueness.
            $table->string('key', 64)->nullable()->after('name');
        });

        // Back-fill: set key = id for existing rows so the unique index below can be applied.
        DB::statement("UPDATE approval_flows SET key = CONCAT('flow_', id) WHERE key IS NULL");

        Schema::table('approval_flows', function (Blueprint $table) {
            $table->string('key', 64)->nullable(false)->change();
            // Unique per company: two flows in the same tenant cannot share the same key.
            $table->unique(['company_id', 'key'], 'approval_flows_company_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropUnique('approval_flows_company_key_unique');
            $table->dropColumn('key');
        });
    }
};
