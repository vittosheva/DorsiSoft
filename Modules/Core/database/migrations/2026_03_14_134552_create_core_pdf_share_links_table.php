<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_pdf_share_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->morphs('shareable');
            $table->foreignId('created_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('core_users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['expires_at', 'revoked_at'], 'core_pdf_share_links_exp_rev_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_pdf_share_links');
    }
};
