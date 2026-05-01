<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpt_saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->json('definition');
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'user_id', 'deleted_at']);
            $table->index(['company_id', 'is_shared', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpt_saved_reports');
    }
};
