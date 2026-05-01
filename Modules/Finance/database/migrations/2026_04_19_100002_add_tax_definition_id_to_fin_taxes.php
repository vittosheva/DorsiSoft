<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_taxes', function (Blueprint $table): void {
            $table->foreignId('tax_definition_id')
                ->nullable()
                ->after('id')
                ->constrained('fin_tax_definitions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fin_taxes', function (Blueprint $table): void {
            $table->dropForeign(['tax_definition_id']);
            $table->dropColumn('tax_definition_id');
        });
    }
};
