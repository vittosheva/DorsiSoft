<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_partner_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('address');
            $table->string('reference', 200)->nullable();
            $table->foreignId('city_id')
                ->nullable()
                ->constrained('core_cities')
                ->nullOnDelete();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['business_partner_id', 'type'], 'core_partner_addresses_bp_type_index');
            $table->index(
                ['business_partner_id', 'is_default', 'is_active'],
                'idx_partner_addresses_bp_default',
            );
        });

        Schema::create('core_partner_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->string('bank_name', 100);
            $table->string('account_type', 20);
            $table->string('account_number', 50);
            $table->string('account_holder', 200)->nullable();
            $table->string('identification', 20)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(
                ['business_partner_id', 'is_default', 'deleted_at'],
                'idx_bank_accounts_bp_default',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_partner_bank_accounts');
        Schema::dropIfExists('core_partner_addresses');
    }
};
