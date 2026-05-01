<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpt_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->foreignId('saved_report_id')->nullable()->constrained('rpt_saved_reports')->nullOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('file_path', 500)->nullable();
            $table->string('file_format', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpt_executions');
    }
};
