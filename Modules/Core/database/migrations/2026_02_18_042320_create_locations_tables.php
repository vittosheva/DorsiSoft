<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // COUNTRIES
        Schema::create('core_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable();
            $table->string('phone_code')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();
        });

        // STATES / PROVINCES
        Schema::create('core_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('core_countries')->cascadeOnDelete();

            $table->string('name');
            $table->string('iso2')->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['country_id', 'name']);
            $table->index('country_id');
        });

        // CITIES
        Schema::create('core_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('core_states')->cascadeOnDelete();

            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->index(['state_id', 'name']);
            $table->index('state_id');
        });

        // COUNTIES
        Schema::create('core_counties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('core_states')->cascadeOnDelete();

            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['state_id', 'name']);
            $table->index('state_id');
        });

        // PARISHES
        Schema::create('core_parishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('core_cities')->cascadeOnDelete();

            $table->string('name');
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['city_id', 'name']);
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_parishes');
        Schema::dropIfExists('core_counties');
        Schema::dropIfExists('core_cities');
        Schema::dropIfExists('core_states');
        Schema::dropIfExists('core_countries');
    }
};
