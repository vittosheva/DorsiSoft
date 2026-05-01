<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('fin_tax_catalogs', 'sys_tax_catalogs');
        Schema::rename('fin_tax_definitions', 'sys_tax_definitions');
        Schema::rename('fin_tax_rules', 'sys_tax_rules');
        Schema::rename('fin_tax_rule_lines', 'sys_tax_rule_lines');
        Schema::rename('fin_tax_withholding_rates', 'sys_tax_withholding_rates');
    }

    public function down(): void
    {
        Schema::rename('sys_tax_catalogs', 'fin_tax_catalogs');
        Schema::rename('sys_tax_definitions', 'fin_tax_definitions');
        Schema::rename('sys_tax_rules', 'fin_tax_rules');
        Schema::rename('sys_tax_rule_lines', 'fin_tax_rule_lines');
        Schema::rename('sys_tax_withholding_rates', 'fin_tax_withholding_rates');
    }
};
