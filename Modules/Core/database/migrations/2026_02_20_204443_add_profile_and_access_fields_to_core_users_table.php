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
        Schema::create('core_establishments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['is_active']);
            $table->index(['code']);
        });

        Schema::create('core_emission_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('establishment_id')->constrained('core_establishments')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['establishment_id', 'code']);
            $table->index(['establishment_id', 'is_active']);
            $table->index(
                ['establishment_id', 'is_default', 'deleted_at'],
                'idx_emission_points_estab_default',
            );
        });

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

        Schema::table('core_users', function (Blueprint $table): void {
            $table->foreign('establishment_id')->references('id')->on('core_establishments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_users', function (Blueprint $table): void {
            $table->dropForeign(['establishment_id']);
        });

        Schema::dropIfExists('core_company_establishment');
        Schema::dropIfExists('core_emission_points');
        Schema::dropIfExists('core_establishments');
    }
};
