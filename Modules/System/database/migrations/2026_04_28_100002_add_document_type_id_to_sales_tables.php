<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'sales_invoices',
        'sales_credit_notes',
        'sales_debit_notes',
        'sales_withholdings',
        'sales_purchase_settlements',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreignId('document_type_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('sys_document_types')
                    ->nullOnDelete();

                $table->index(['company_id', 'document_type_id'], "idx_{$tableName}_company_doc_type");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("idx_{$tableName}_company_doc_type");
                $table->dropForeign(['document_type_id']);
                $table->dropColumn('document_type_id');
            });
        }
    }
};
