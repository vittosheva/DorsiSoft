<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('movement_type', ['in', 'out', 'transfer', 'adjustment']);
            $table->boolean('affects_inventory')->default(true);
            $table->boolean('requires_source_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['movement_type', 'is_active'], 'inv_doc_types_movement_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_document_types');
    }
};
