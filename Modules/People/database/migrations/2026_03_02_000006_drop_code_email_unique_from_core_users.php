<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_users', function (Blueprint $table): void {
            $table->dropUnique('core_users_company_id_code_unique');
            $table->dropUnique('core_users_company_id_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('core_users', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'core_users_company_id_code_unique');
            $table->unique(['company_id', 'email'], 'core_users_company_id_email_unique');
        });
    }
};
