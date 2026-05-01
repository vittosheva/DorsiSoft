<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_withholding_items', function (Blueprint $table): void {
            $table->string('source_document_type', 20)->nullable()->change();
            $table->string('source_document_number', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_withholding_items', function (Blueprint $table): void {
            $table->string('source_document_type', 5)->nullable()->change();
            $table->string('source_document_number', 17)->nullable()->change();
        });
    }
};
