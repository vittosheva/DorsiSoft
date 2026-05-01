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
        Schema::create('core_business_partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('identification_type', 20);
            $table->string('identification_number', 30);
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('tax_address')->nullable();
            $table->foreignId('country_id')->nullable()->index();
            $table->foreignId('state_id')->nullable()->index();
            $table->foreignId('city_id')->nullable()->index();
            $table->foreignId('parish_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->string('ocr_document_path')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'identification_type', 'identification_number'], 'core_bp_company_ident_unique');
            $table->index(['company_id', 'legal_name']);
            $table->index(['company_id', 'is_active', 'deleted_at'], 'idx_bp_company_active');
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['legal_name', 'trade_name'], 'ftidx_bp_names');
            }
        });

        Schema::create('core_partner_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code', 'idx_partner_roles_code');
        });

        Schema::create('core_business_partner_role', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')->constrained('core_business_partners')->cascadeOnDelete();
            $table->foreignId('partner_role_id')->constrained('core_partner_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_partner_id', 'partner_role_id'], 'core_bp_role_unique');
            $table->index('partner_role_id', 'idx_bp_role_partner_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_business_partner_role');
        Schema::dropIfExists('core_partner_roles');
        Schema::dropIfExists('core_business_partners');
    }
};
