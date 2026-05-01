<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_emission_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('core_users')->cascadeOnDelete();
            $table->foreignId('emission_point_id')->constrained('core_emission_points')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->foreignId('payment_method_id')->nullable()->constrained('core_payment_methods');
            $table->foreignId('cash_register_id')->nullable()->constrained('core_cash_registers');
            $table->boolean('allow_mixed_payments')->default(false);
            $table->boolean('restrict_payment_methods')->default(false);
            $table->boolean('require_shift')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'emission_point_id']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_emission_points');
    }
};
