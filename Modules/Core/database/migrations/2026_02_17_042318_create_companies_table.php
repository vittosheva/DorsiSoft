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
        Schema::create('core_companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('ruc', 13)->unique();
            $table->boolean('is_accounting_required')->default(false);
            $table->boolean('is_special_taxpayer')->default(false);
            $table->string('special_taxpayer_resolution')->nullable();
            $table->string('tax_regime')->default('GENERAL');
            $table->string('contributor_status')->nullable();
            $table->string('taxpayer_type')->nullable();
            $table->string('contributor_category')->nullable();
            $table->boolean('is_withholding_agent')->default(false);
            $table->boolean('is_shell_company')->default(false);
            $table->boolean('has_nonexistent_transactions')->default(false);
            $table->timestamp('started_activities_at')->nullable();
            $table->timestamp('ceased_activities_at')->nullable();
            $table->timestamp('restarted_activities_at')->nullable();
            $table->timestamp('sri_updated_at')->nullable();
            $table->json('legal_representatives')->nullable();
            $table->text('suspension_cancellation_reason')->nullable();
            $table->date('rimpe_expires_at')->nullable();
            $table->string('economic_activity_code')->nullable();
            $table->text('business_activity')->nullable();
            $table->string('tax_address')->nullable();
            $table->foreignId('country_id')->nullable()->index();
            $table->foreignId('state_id')->nullable()->index();
            $table->foreignId('city_id')->nullable()->index();
            $table->foreignId('parish_id')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedBigInteger('default_currency_id')->nullable()->index();
            $table->string('timezone')->nullable();
            $table->string('sri_environment', 20)->default('pruebas');
            $table->string('logo_url')->nullable();
            $table->string('logo_pdf_url')->nullable();
            $table->string('logo_isotype_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        Schema::create('core_company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();

            $table->primary(['company_id', 'user_id']);
        });

        Schema::table('core_users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('core_companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::dropIfExists('core_company_user');
        Schema::dropIfExists('core_companies');
    }
};
