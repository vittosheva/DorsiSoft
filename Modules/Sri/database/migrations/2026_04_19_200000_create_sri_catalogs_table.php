<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sri_catalogs', function (Blueprint $table): void {
            $table->id();
            $table->string('catalog_type', 30);
            $table->string('code', 20);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('extra_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_type', 'code'], 'sri_catalogs_type_code_unique');
            $table->index(['catalog_type', 'is_active'], 'sri_catalogs_type_active_index');
            $table->index(['catalog_type', 'sort_order'], 'sri_catalogs_type_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sri_catalogs');
    }
};
