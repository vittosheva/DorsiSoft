<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sri_technical_exchanges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->morphs('documentable');
            $table->string('service', 50);
            $table->string('operation', 80);
            $table->string('environment', 20)->nullable();
            $table->string('endpoint')->nullable();
            $table->string('status', 35);
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->longText('request_body')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'documentable_type', 'documentable_id', 'created_at'], 'sri_tech_exchange_document_idx');
            $table->index(['company_id', 'service', 'status', 'created_at'], 'sri_tech_exchange_service_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sri_technical_exchanges');
    }
};
