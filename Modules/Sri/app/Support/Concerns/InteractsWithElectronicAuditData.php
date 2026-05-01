<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Support\ElectronicAudit\ElectronicAuditDataResolver;

trait InteractsWithElectronicAuditData
{
    public ?Model $record = null;

    protected ?array $cachedAuditData = null;

    /**
     * @return array<string, mixed>
     */
    public function getAuditData(): array
    {
        return $this->cachedAuditData ??= app(ElectronicAuditDataResolver::class)->resolve($this->record);
    }

    protected function hasElectronicAuditRecord(): bool
    {
        return $this->record instanceof HasElectronicBilling;
    }
}
