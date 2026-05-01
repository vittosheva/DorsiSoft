<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpt_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->string('role', 100)->nullable();
            $table->string('widget_class', 300);
            $table->string('title', 150)->nullable();
            $table->json('position')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'is_active']);
            $table->index(['company_id', 'role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpt_dashboard_widgets');
    }
};
