<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\System\Enums\TaxAppliesToEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('applies_to', 20)->default(TaxAppliesToEnum::Ambos->value);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->json('conditions')->nullable();
            $table->foreignId('tax_definition_id')->constrained('fin_tax_definitions');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('core_users');
            $table->foreignId('updated_by')->nullable()->constrained('core_users');
            $table->timestamps();

            $table->index(['applies_to', 'is_active'], 'fin_tax_rules_applies_active_index');
            $table->index(['tax_definition_id'], 'fin_tax_rules_definition_index');
            $table->index(['is_active', 'valid_from'], 'fin_tax_rules_active_from_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_tax_rules');
    }
};
