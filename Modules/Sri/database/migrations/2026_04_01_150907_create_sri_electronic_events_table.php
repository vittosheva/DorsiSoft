<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sri_electronic_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->morphs('documentable');
            $table->string('event', 50);
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30)->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'documentable_type', 'documentable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sri_electronic_events');
    }
};
