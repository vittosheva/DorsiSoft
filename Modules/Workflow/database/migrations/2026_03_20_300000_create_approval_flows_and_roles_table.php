<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->cascadeOnDelete();
            $table->string('name');
            // FK temporalmente comentada hasta que exista document_types
            // $table->foreignId('document_type_id')->constrained('document_types');
            $table->unsignedBigInteger('document_type_id');
            $table->boolean('is_active')->default(true);
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('approval_flow_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles');
            $table->unsignedTinyInteger('step')->default(1);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_roles');
        Schema::dropIfExists('approval_flows');
    }
};
