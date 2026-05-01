<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_price_lists', function (Blueprint $table): void {
            $table->dropUnique('sales_price_lists_company_code_unique');
            $table->dropUnique('sales_price_lists_company_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales_price_lists', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'sales_price_lists_company_code_unique');
            $table->unique(['company_id', 'name'], 'sales_price_lists_company_name_unique');
        });
    }
};
