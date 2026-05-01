<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_companies', function (Blueprint $table): void {
            $table->string('certificate_path')->nullable()->after('sri_environment');
            $table->string('certificate_password_encrypted')->nullable()->after('certificate_path');
            $table->date('certificate_valid_from')->nullable()->after('certificate_password_encrypted');
            $table->date('certificate_expiration_date')->nullable()->after('certificate_valid_from');
        });
    }

    public function down(): void
    {
        Schema::table('core_companies', function (Blueprint $table): void {
            $table->dropColumn(['certificate_path', 'certificate_password_encrypted', 'certificate_valid_from', 'certificate_expiration_date']);
        });
    }
};
