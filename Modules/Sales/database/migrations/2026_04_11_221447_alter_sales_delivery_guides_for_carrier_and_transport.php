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
            $table->string('carrier_type', 20)->default('third_party')->after('carrier_plate');
            $table->renameColumn('transport_date', 'transport_start_date');
            $table->date('transport_end_date')->nullable()->after('transport_start_date');

            $table->dropForeign(['recipient_id']);
            $table->dropColumn([
                'recipient_id',
                'recipient_name',
                'recipient_identification_type',
                'recipient_identification',
                'recipient_address',
                'destination_address',
                'route',
                'transport_reason',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sales_delivery_guides', function (Blueprint $table): void {
            $table->dropColumn(['carrier_type', 'transport_end_date']);
            $table->renameColumn('transport_start_date', 'transport_date');

            $table->foreignId('recipient_id')->nullable()->constrained('core_business_partners')->nullOnDelete();
            $table->string('recipient_name', 200)->nullable();
            $table->string('recipient_identification_type', 20)->nullable();
            $table->string('recipient_identification', 30)->nullable();
            $table->string('recipient_address', 300)->nullable();
            $table->string('destination_address', 300)->nullable();
            $table->string('route', 300)->nullable();
            $table->string('transport_reason', 300)->nullable();
        });
    }
};
