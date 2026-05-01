<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateTaxWithholdingRuleChangesTable extends Migration
{
    public function up(): void
    {
        Schema::create('sales_tax_withholding_rule_changes', function (Blueprint $table): void {
            $table->id();
            // create rule_id as unsignedBigInteger to allow adding FK later if target table exists
            $table->unsignedBigInteger('rule_id');
            $table->decimal('old_percentage', 8, 4)->nullable();
            $table->decimal('new_percentage', 8, 4);
            $table->string('reason')->nullable();
            $table->userstamps();
            $table->timestamps();
        });

        // Add foreign key constraint only if the referenced table already exists
        if (Schema::hasTable('sales_tax_withholding_rules')) {
            Schema::table('sales_tax_withholding_rule_changes', function (Blueprint $table): void {
                $table->foreign('rule_id')
                    ->references('id')
                    ->on('sales_tax_withholding_rules')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_tax_withholding_rule_changes');
    }
}
