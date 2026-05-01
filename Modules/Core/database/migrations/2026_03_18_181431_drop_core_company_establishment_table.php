<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('core_company_establishment');
    }

    public function down(): void
    {
        Schema::create('core_company_establishment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained('core_establishments')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'establishment_id']);
            $table->index(['company_id', 'is_primary']);
            $table->index(['establishment_id', 'is_active']);
        });
    }
};
