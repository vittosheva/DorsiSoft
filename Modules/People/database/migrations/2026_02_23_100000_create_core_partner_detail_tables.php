<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_customer_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->unique()
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->string('seller_name', 150)->nullable();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->decimal('credit_balance', 14, 2)->default(0);
            $table->smallInteger('payment_terms_days')->nullable();
            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->boolean('tax_exempt')->default(false);
            $table->string('rating', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        Schema::create('core_supplier_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->unique()
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->smallInteger('payment_terms_days')->nullable();
            $table->smallInteger('lead_time_days')->nullable();
            $table->boolean('tax_withholding_applicable')->default(false);
            $table->string('rating', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        Schema::create('core_carrier_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->unique()
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->string('transport_authorization', 100)->nullable();
            $table->date('authorization_expiry_date')->nullable();
            $table->string('soat_number', 50)->nullable();
            $table->date('soat_expiry_date')->nullable();
            $table->string('cargo_insurance_number', 50)->nullable();
            $table->date('cargo_insurance_expiry_date')->nullable();
            $table->string('insurance_company', 200)->nullable();
            $table->decimal('insurance_coverage_amount', 15, 2)->nullable();
            $table->string('rating', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        Schema::create('core_carrier_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_partner_id')
                ->constrained('core_business_partners')
                ->cascadeOnDelete();
            $table->string('driver_name', 200);
            $table->string('driver_identification', 20);
            $table->string('driver_license', 50)->nullable();
            $table->string('driver_license_type', 10)->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('vehicle_plate', 20);
            $table->string('vehicle_type', 50)->nullable();
            $table->string('vehicle_brand', 100)->nullable();
            $table->string('vehicle_model', 100)->nullable();
            $table->smallInteger('vehicle_year')->nullable();
            $table->decimal('vehicle_capacity_tons', 10, 2)->nullable();
            $table->decimal('vehicle_capacity_m3', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['business_partner_id', 'vehicle_plate'], 'core_carrier_vehicles_bp_plate_unique');
            $table->index(
                ['business_partner_id', 'is_active', 'deleted_at'],
                'idx_carrier_vehicles_bp_active',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_carrier_vehicles');
        Schema::dropIfExists('core_carrier_details');
        Schema::dropIfExists('core_supplier_details');
        Schema::dropIfExists('core_customer_details');
    }
};
