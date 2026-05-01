<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed canonical types before adding the FK constraint so existing
        // approval_flows rows (with document_type_id 1-5) remain valid.
        DB::table('workflow_document_types')->insert([
            ['code' => 'sales', 'name' => 'Sales', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'purchase', 'name' => 'Purchase', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'finance', 'name' => 'Finance', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'inventory', 'name' => 'Inventory', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'general', 'name' => 'General', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('approval_flows', function (Blueprint $table) {
            $table->foreign('document_type_id')
                ->references('id')
                ->on('workflow_document_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
        });

        Schema::dropIfExists('workflow_document_types');
    }
};
