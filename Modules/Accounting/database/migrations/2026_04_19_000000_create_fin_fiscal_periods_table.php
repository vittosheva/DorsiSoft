<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('core_companies')->restrictOnDelete();
            $table->integer('year');
            $table->tinyInteger('month');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default(FiscalPeriodStatusEnum::OPEN)->comment('OPEN, CLOSED');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_id')->nullable()->constrained('core_users')->nullOnDelete();
            $table->timestamps();
            $table->userstamps();
            $table->userstampSoftDeletes();
            $table->softDeletes();

            $table->unique(['company_id', 'year', 'month']);
            $table->index(['company_id', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_fiscal_periods');
    }
};
