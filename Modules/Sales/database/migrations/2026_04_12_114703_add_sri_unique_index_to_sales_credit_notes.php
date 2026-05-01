<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->unique(
                ['company_id', 'establishment_code', 'emission_point_code', 'sequential_number'],
                'uq_credit_note_sri_seq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table): void {
            $table->dropUnique('uq_credit_note_sri_seq');
        });
    }
};
