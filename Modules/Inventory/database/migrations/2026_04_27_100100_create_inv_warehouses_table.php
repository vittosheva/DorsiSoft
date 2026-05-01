<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_warehouses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained('core_establishments')->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'inv_warehouses_company_code_unique');
            $table->index(['company_id', 'is_active', 'deleted_at'], 'inv_warehouses_company_active_deleted_index');
            $table->index(['company_id', 'is_default', 'deleted_at'], 'inv_warehouses_company_default_deleted_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_warehouses');
    }
};
