<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_delivery_guides', function (Blueprint $table): void {
            $table->foreignId('document_type_id')
                ->nullable()
                ->after('company_id')
                ->constrained('sys_document_types')
                ->nullOnDelete();

            $table->index(
                ['company_id', 'document_type_id'],
                'idx_sales_delivery_guides_company_doc_type'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_delivery_guides', function (Blueprint $table): void {
            $table->dropIndex('idx_sales_delivery_guides_company_doc_type');
            $table->dropForeign(['document_type_id']);
            $table->dropColumn('document_type_id');
        });
    }
};
