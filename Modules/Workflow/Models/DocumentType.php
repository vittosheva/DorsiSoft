<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DocumentType extends Model
{
    protected $table = 'workflow_document_types';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function approvalFlows(): HasMany
    {
        return $this->hasMany(ApprovalFlow::class);
    }
}
