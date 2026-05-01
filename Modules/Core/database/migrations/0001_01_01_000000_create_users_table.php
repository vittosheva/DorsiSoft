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
        Schema::create('core_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('language', 10)->default('es');
            $table->string('timezone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedBigInteger('establishment_id')->nullable();
            $table->boolean('is_allowed_to_login')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'email']);
            $table->unique(['company_id', 'code']);
            $table->index('company_id');
            $table->index('establishment_id');
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name'], 'ftidx_users_name');
            }
            $table->index(['company_id', 'is_active', 'deleted_at'], 'idx_users_company_active');
            $table->index(
                ['company_id', 'is_allowed_to_login', 'is_active'],
                'idx_users_company_login_active',
            );
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained('core_users')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('core_users');
    }
};
