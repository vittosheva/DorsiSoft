<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('inv_categories')->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            // Accounting accounts — nullable, FK to fin_accounts will be added when that table exists
            $table->unsignedBigInteger('sales_account_id')->nullable();
            $table->unsignedBigInteger('purchase_account_id')->nullable();
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['company_id', 'parent_id', 'deleted_at'], 'inv_categories_company_parent_deleted_index');
            $table->index(['company_id', 'is_active', 'deleted_at'], 'inv_categories_company_active_deleted_index');
        });

        Schema::create('inv_brands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'deleted_at'], 'inv_brands_company_active_deleted_index');
        });

        Schema::create('inv_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'deleted_at'], 'inv_units_company_active_deleted_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_units');
        Schema::dropIfExists('inv_brands');
        Schema::dropIfExists('inv_categories');
    }
};
