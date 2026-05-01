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
        Schema::create('core_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        Schema::table('core_companies', function (Blueprint $table): void {
            $table->foreign('default_currency_id')->references('id')->on('core_currencies')->nullOnDelete();
        });

        Schema::create('core_company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('plan_code', 32);
            $table->string('status', 32)->default('active');
            $table->string('billing_cycle', 32)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'starts_at']);
            $table->index(['company_id', 'ends_at']);

            $table->index(
                ['company_id', 'status', 'starts_at', 'ends_at'],
                'core_company_subscriptions_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_company_subscriptions');

        Schema::table('core_companies', function (Blueprint $table): void {
            $table->dropForeign(['default_currency_id']);
        });

        Schema::dropIfExists('core_currencies');
    }
};
