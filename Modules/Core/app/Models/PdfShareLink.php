<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\People\Models\User;

final class PdfShareLink extends Model
{
    protected $table = 'core_pdf_share_links';

    protected $fillable = [
        'token',
        'shareable_type',
        'shareable_id',
        'created_by',
        'expires_at',
        'last_accessed_at',
        'revoked_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by')->withoutGlobalScopes();
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked();
    }
}
